<?php

namespace App;

use phpDocumentor\Reflection\Types\Integer;

class ApiProblem
{
    private $status;

    private $developerMessage;

    private $userMessage;

    private $errorCode;

    private $moreInfo;

    public function __construct(string $status, string $developerMessage, string $userMessage)
    {
        $this->status = $status;
        $this->developerMessage = $developerMessage;
        $this->userMessage = $userMessage;
        $this->errorCode = 1;
        $this->moreInfo = $_ENV["SITE_BASE_URL"] . '/api/doc';
    }
}