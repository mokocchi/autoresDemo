<?php

namespace App\Security;

use App\ApiProblem;
use FOS\OAuthServerBundle\Security\EntryPoint\OAuthEntryPoint as BaseOAuthEntryPoint;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class OAuthEntryPoint extends BaseOAuthEntryPoint
{
    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new JsonResponse(
            new ApiProblem(Response::HTTP_UNAUTHORIZED, "Se requiere autenticación OAuth", "Se requiere autenticación"),
            Response::HTTP_UNAUTHORIZED
        );
    }
}
