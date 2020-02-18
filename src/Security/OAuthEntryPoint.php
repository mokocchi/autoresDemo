<?php

namespace App\Security;

use App\ApiProblem;
use FOS\OAuthServerBundle\Security\EntryPoint\OAuthEntryPoint as BaseOAuthEntryPoint;
use JMS\Serializer\SerializerInterface;
use OAuth2\OAuth2;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class OAuthEntryPoint extends BaseOAuthEntryPoint
{
    private $serializer;
    public function __construct(OAuth2 $oAuth2, SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $apiProblem = new ApiProblem(Response::HTTP_UNAUTHORIZED, "Se requiere autenticación OAuth", "Se requiere autenticación");
        return new Response(
            $this->serializer->serialize($apiProblem, "json"),
            Response::HTTP_UNAUTHORIZED
        );
    }
}
