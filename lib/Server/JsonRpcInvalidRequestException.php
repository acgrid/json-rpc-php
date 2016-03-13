<?php

namespace pozo\jsonrpc\Server;

/**
 * Class JsonRpcInvalidRequestException
 * The JSON sent is not a valid Request object.
 * @package pozo\jsonrpc\Server
 */
class JsonRpcInvalidRequestException extends \RuntimeException {
    public function __construct() {
        parent::__construct("Invalid Request", -32600);
    }
}