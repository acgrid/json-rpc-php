<?php

namespace pozo\jsonrpc\Server;

/**
 * Class JsonRpcServerErrorException
 * Reserved for implementation-defined server-errors.
 * @package pozo\jsonrpc\Server
 */
class JsonRpcServerErrorException extends \RuntimeException
{
    public function __construct() {
        parent::__construct("Server error",-32099||-32000);
    }
}