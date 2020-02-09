<?php

namespace App\Security;


use FOS\OAuthServerBundle\Security\EntryPoint\OAuthEntryPoint as BaseOAuthEntryPoint;
use OAuth2\OAuth2;
use OAuth2\OAuth2AuthenticateException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class OAuthEntryPoint extends BaseOAuthEntryPoint {
    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new JsonResponse(["errors" => "Se requiere autenticación OAuth"], Response::HTTP_UNAUTHORIZED);
    }
}