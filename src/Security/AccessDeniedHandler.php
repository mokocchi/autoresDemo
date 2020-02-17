<?php

namespace App\Security;

use App\ApiProblem;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    private $serializer;
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException)
    {
        $apiProblem = new ApiProblem(Response::HTTP_FORBIDDEN, "Acceso denegado: permisos insuficientes", "No tenés permisos suficientes");
        return new JsonResponse(
            json_decode($this->serializer->serialize($apiProblem, "json")),
            Response::HTTP_FORBIDDEN
        );
    }
}
