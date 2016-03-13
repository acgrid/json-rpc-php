<?php

namespace pozo\jsonrpc\Server;


/**
 * Class JsonRpcInvalidParamsException
 * Invalid method parameter(s).
 * @package pozo\jsonrpc\Server
 */
class JsonRpcInvalidParamsException extends \InvalidArgumentException {
    public function __construct() {
        parent::__construct("Invalid params",-32602);
    }
}