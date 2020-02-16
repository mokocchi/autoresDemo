<?php

namespace App\Security;

use App\ApiProblem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function handle(Request $request, AccessDeniedException $accessDeniedException)
    {
        return new JsonResponse(
            new ApiProblem(Response::HTTP_FORBIDDEN, "Acceso denegado: permisos insuficientes", "No tenés permisos suficientes"),
            Response::HTTP_FORBIDDEN
        );
    }
}
