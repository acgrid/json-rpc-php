<?php

namespace pozo\jsonrpc\Server;

/**
 * Class JsonRpcInternalErrorException
 * Internal JSON-RPC error.
 * @package pozo\jsonrpc\Server
 */
class JsonRpcInternalErrorException extends \RuntimeException {
    public function __construct() {
        parent::__construct("Internal error",-32603);
    }
}