<?php

namespace pozo\jsonrpc\Server;
use \stdClass;
use \Exception;

class JsonRpcServer {
	private $_requestText;
	private $_listOfCallableServices;
	protected $_requestObject;
	private $_responseBatchArray = array();

	function __construct($postRequest) {
		$this->_requestText = $postRequest;
		$this->_listOfCallableServices = array();
	}
	public function addService(JsonRpcService $classInstance) {
        array_push($this->_listOfCallableServices, $classInstance);
	}
	public function processingRequests() {
		try {
			$this->parseRequestJson();
			$this->performCalls();
		} catch(Exception $e) {
			$responseBody = new RpcError($e->getMessage(),$e->getCode());
			$responseObject = new RpcResponse($responseBody);
			$this->doResponse($responseObject->getRpcResponseObject());
		}
	}
	protected function parseRequestJson() {
		if(!is_null($requestObjects = json_decode($this->_requestText))) {
			$this->_requestObject = $requestObjects;
		} else {
			throw new JsonRpcParseErrorException();
		}
	}
	protected function performCalls() {
		if($this->isBatchRequestAndNotEmpty()) {
			$this->performBatchCall();
		} else {
			$this->performSingleCall();
		}
	}
	private function isBatchRequestAndNotEmpty() {
		if(is_array($this->_requestObject)) {
			if(empty($this->_requestObject)) {
				throw new JsonRpcInvalidRequestException();
			}
			return true;
		} else {
			return false;
		}
	}
	private function performBatchCall() {
		foreach ($this->_requestObject as $request) {
			$responseObject = $this->getResponseObject($request);
			if (!$this->isNotification($request)) {
				array_push($this->_responseBatchArray, $responseObject->getRpcResponseObject());
			}
		}
		$this->doResponse($this->_responseBatchArray);
	}
	private function performSingleCall() {
		$responseObject = $this->getResponseObject($this->_requestObject);
		$obj = $responseObject->getRpcResponseObject();
		if (!$this->isNotification($this->_requestObject)) {
			$this->doResponse($obj);
		}
	}
	private function isNotification($requestObject) {
		return is_object($requestObject) && is_null($requestObject->id);
	}
	private function getResponseObject($requestObject) {
		try {
			$this->validateRequest($requestObject);
			$methodOwnerService = $this->isMethodAvailable($requestObject);
			$this->validateAndSortParameters($methodOwnerService, $requestObject);
			$responseObject = $this->buildResponseObject($requestObject, $methodOwnerService);
		} catch(Exception $exception) {
			$responseObject = $this->buildResponseObject($exception);
			$responseObject->setResponseObjectId($requestObject->id);
		}
		return $responseObject;
	}
	private function validateRequest($request) {
		if(!$this->isValidRequestObject($request)) {
			throw new JsonRpcInvalidRequestException();
		} else {
			return true;
		}
	}
	private function isValidRequestObject($requestObject) {
		return ($requestObject->jsonrpc == RpcResponse::VERSION
		       && $this->isValidRequestObjectId($requestObject->id)
			   && $this->isValidRequestObjectMethod($requestObject->method));
	}
	private function isValidRequestObjectId($requestId) {
		return (is_null($requestId)
        		// 2 and "2" is valid but 2.1 and "2.1" is not
		        || (is_string($requestId) && ctype_digit($requestId))
		        || is_int($requestId));
	}
	private function isValidRequestObjectMethod($requestMethod) {
		return (!is_null($requestMethod)
			   && is_string($requestMethod)
			   && strncmp($reserved = "rpc.",$requestMethod,strlen($reserved)));
	}

	/**
	 * @param $requestObject
	 * @throws JsonRpcMethodNotFoundException
	 * @return JsonRpcService
	 */
	protected function isMethodAvailable($requestObject) {
        $length = count($this->_listOfCallableServices);
        for($i=0;$i<$length;$i++) {
			$service =  $this->_listOfCallableServices[$i];
			if($service instanceof JsonRpcService && array_key_exists($requestObject->method, $service->getCallableMethodNames())) {
    			return $service;
			}
        }
		throw new JsonRpcMethodNotFoundException();
	}
	private function validateAndSortParameters(JsonRpcService $methodOwnerService, $requestObject) {
		$validParameters = $methodOwnerService->getCallableMethodParameters($requestObject->method);

		if($this->isValidParamsNumber($validParameters, $requestObject)
		   && $this->isValidParamsName($validParameters, $requestObject)) {

			$this->setMethodParamsSequence($validParameters, $requestObject);
		} else {
			throw new JsonRpcInvalidParamsException();
		}
	}
	private function setMethodParamsSequence($validParameters, $requestParameters) {
		$sortedObject = new stdClass();
		if(is_object($requestParameters->params)) {
			foreach($validParameters as $parameter) {
				$sortedObject->{$parameter->name} = $requestParameters->params->{$parameter->name};
			}
			$requestParameters->params = $sortedObject;
		}
	}
	private function buildResponseObject($requestOrExceptionObject, $service = null) {
		if(is_null($service) && $requestOrExceptionObject instanceof Exception) {
			$responseBody = new RpcError($requestOrExceptionObject->getMessage(),$requestOrExceptionObject->getCode());
			$responseObject = new RpcResponse($responseBody);
		} else {
			$callbackResult = $this->call($service, $requestOrExceptionObject);
			$responseObject = new RpcResponse($callbackResult, $requestOrExceptionObject->id);
		}
		return $responseObject;
	}
	private function isValidParamsNumber($validParameters, $requestObject) {
		$validParameterCount = count($validParameters);
		$requestParameterCount = $this->countRequestParams($requestObject->params);

		if($validParameterCount != $requestParameterCount) {
			return false;
		}
		return true;
	}
	private function countRequestParams($requestObjectParams) {
		if(is_object($requestObjectParams)) {
			return count(get_object_vars($requestObjectParams));
		} else {
			return count($requestObjectParams);
		}
	}
	private function isValidParamsName($validParameters, $requestObject) {
		if(is_object($requestObject->params)){
			$requestParamNames = array_keys(get_object_vars($requestObject->params));
			foreach($validParameters as $parameter) {
				if(!in_array($parameter->name, $requestParamNames, true)) {
					return false;
				}
			}
			return true;
		} else {
			return true;
		}
	}
	private function call($methodOwnerService, $requestObject) {
		$callbackFunction = array($methodOwnerService,$requestObject->method);
		return call_user_func_array($callbackFunction, is_string($requestObject->params) ? [$requestObject->params] : $requestObject->params);
	}
	protected function doResponse($responseObject) {
		if(!empty($responseObject)) {
			header('Content-Type: application/json');
			echo json_encode($responseObject);
		}
	}
}
