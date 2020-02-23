<?php

namespace App\Api;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ApiProblemExceptionTest extends TestCase
{
    public function testGetApiProblem()
    {
        try {
            throw new ApiProblemException(
                new ApiProblem(
                    Response::HTTP_INTERNAL_SERVER_ERROR,
                    "Error para desarrolladores",
                    "Error para el usuario"
                )
            );
            $this->fail("La excepción no fue lanzada");
        } catch (ApiProblemException $e) {
            $apiProblem = $e->getApiProblem();
            $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $apiProblem->getStatusCode());
            $this->assertEquals("Error para desarrolladores", $apiProblem->getDeveloperMessage());
            $this->assertEquals("Error para el usuario", $apiProblem->getUserMessage());
        }
    }
}
