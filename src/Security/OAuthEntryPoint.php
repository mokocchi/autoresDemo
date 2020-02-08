<?php

namespace App\Security;


use FOS\OAuthServerBundle\Security\EntryPoint\OAuthEntryPoint as BaseOAuthEntryPoint;
use OAuth2\OAuth2;
use OAuth2\OAuth2AuthenticateException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class OAuthEntryPoint extends BaseOAuthEntryPoint {
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $exception = new OAuth2AuthenticateException(
            Response::HTTP_UNAUTHORIZED,
            OAuth2::TOKEN_TYPE_BEARER,
            $this->serverService->getVariable(OAuth2::CONFIG_WWW_REALM),
            'acceso_denegado',
            'Se requiere autenticación OAuth2'
        );

        return $exception->getHttpResponse();
    }
}