<?php

namespace App\Security;

use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use FOS\OAuthServerBundle\Security\EntryPoint\OAuthEntryPoint as BaseOAuthEntryPoint;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class OAuthEntryPoint extends BaseOAuthEntryPoint
{
    public function start(Request $request, AuthenticationException $authException = null)
    {   
        throw new ApiProblemException(
            new ApiProblem(Response::HTTP_UNAUTHORIZED, "Se requiere autenticación OAuth", "Se requiere autenticación")
        );
    }
}
