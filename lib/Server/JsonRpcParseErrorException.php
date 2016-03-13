<?php

namespace pozo\jsonrpc\Server;

/**
 * Class JsonRpcParseErrorException
 * Invalid JSON was received by the server.
 * An error occurred on the server while parsing the JSON text.
 * @package pozo\jsonrpc\Server
 */
class JsonRpcParseErrorException extends \RuntimeException {
    public function __construct() {
        parent::__construct("Parse error",-32700);
    }
}