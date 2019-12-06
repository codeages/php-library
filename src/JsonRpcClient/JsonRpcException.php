<?php

namespace Codeages\Library\JsonRpcClient;

class JsonRpcException extends \Exception
{
    private $data;

    public function __construct($message = "", $code = 0, array $data = null)
    {
        parent::__construct($message, $code);
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }
}