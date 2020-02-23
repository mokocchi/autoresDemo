<?php

namespace App\Api;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiProblemResponseFactoryTest extends TestCase
{
    public function testCreateResponse()
    {
        $apiProblem = new ApiProblem(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            "Error para desarrolladores",
            "Error para el usuario"
        );
        $responseFactory = new ApiProblemResponseFactory();
        $response = $responseFactory->createResponse($apiProblem);
        $data = json_decode((string) $response->getContent(), true);
        $this->assertTrue($response instanceof JsonResponse);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertEquals(["status",
        "developer_message",
        "user_message",
        "error_code",
        "more_info"], array_keys($data));
    }
}
