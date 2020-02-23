<?php

namespace App\Api;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ApiProblemTest extends TestCase
{
    public function testCreate()
    {
        $apiProblem = new ApiProblem(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            "Error para desarrolladores",
            "Error para el usuario"
        );
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $apiProblem->getStatusCode());
        $this->assertEquals("Error para desarrolladores", $apiProblem->getDeveloperMessage());
        $this->assertEquals("Error para el usuario", $apiProblem->getUserMessage());
    }

    public function testToArray()
    {
        $apiProblem = new ApiProblem(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            "Error para desarrolladores",
            "Error para el usuario",
            123
        );
        $array = $apiProblem->toArray();
        $this->assertEquals([
            "status",
            "developer_message",
            "user_message",
            "error_code",
            "more_info"
        ], array_keys($array));
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $array["status"]);
        $this->assertEquals("Error para desarrolladores", $array["developer_message"]);
        $this->assertEquals("Error para el usuario", $array["user_message"]);
        $this->assertEquals(123, $array["error_code"]);
        $this->assertEquals($_ENV["SITE_BASE_URL"] . '/api/doc', $array["more_info"]);
    }
}
