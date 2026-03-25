<?php

namespace Avangard\Lib;

class Response
{
    private $body;
    private $statusCode;
    private $errorMessage;

    public function __construct($body, $statusCode, $errorMessage)
    {
        $this->body = $body;
        $this->statusCode = $statusCode;
        $this->errorMessage = $errorMessage;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}