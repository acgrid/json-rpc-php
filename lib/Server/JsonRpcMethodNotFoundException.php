<?php

namespace pozo\jsonrpc\Server;


/**
 * Class JsonRpcMethodNotFoundException
 * The method does not exist / is not available.
 * @package pozo\jsonrpc\Server
 */
class JsonRpcMethodNotFoundException extends \BadMethodCallException {
    public function __construct() {
        parent::__construct("Method not found",-32601);
    }
}