<?php

// AppBundle\Controller\TokenController.php

namespace App\Controller\auth;

use App\Entity\Usuario;
use AppBundle\Entity\OAuthAccessToken;
use AppBundle\Entity\Manager\OAuthClientManager;
use Doctrine\ORM\EntityManagerInterface;
use FOS\OAuthServerBundle\Controller\TokenController as BaseTokenController;
use FOS\OAuthServerBundle\Model\AccessTokenManagerInterface;
use FOS\OAuthServerBundle\Model\ClientManagerInterface;
use Google_Client;
use OAuth2\OAuth2;
use OAuth2\OAuth2ServerException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TokenController extends BaseTokenController
{
  protected $clientManager;
  protected $tokenManager;
  protected $entityManager;
  public function __construct(OAuth2 $server, EntityManagerInterface $entityManager)
  {
    parent::__construct($server);
    $this->em = $entityManager;
  }

  public function tokenAction(Request $request)
  {
    if ($request === null) {
      $request = Request::createFromGlobals();
    }

    $property = $request->isMethod(Request::METHOD_POST) ? 'request' : 'query';
    $header = $request->headers->get('X-AUTH-TOKEN');
    if (is_null($header)) {
      $header = $request->headers->get('X-AUTH-CREDENTIALS');
      if (is_null($header)) {
        return new JsonResponse(['errors' => 'No se encontró el header'], Response::HTTP_BAD_REQUEST);
      }
      $request->headers->remove('X-AUTH-CREDENTIALS');
      $request->$property->set('grant_type', OAuth2::GRANT_TYPE_CLIENT_CREDENTIALS);
      $response = parent::tokenAction($request);
      if ($response->getStatusCode() == Response::HTTP_OK) {
        return $response;
      } else {
        return new JsonResponse(['errors' => 'Credenciales inválidas o faltantes'], Response::HTTP_BAD_REQUEST);
      }
    }
    $id_token = $request->$property->get('token');
    $request->$property->remove('token');

    if (is_null($id_token)) {
      return new JsonResponse(['errors' => 'No se encontró el token'], Response::HTTP_BAD_REQUEST);
    }

    if (!(preg_match('/^[A-Za-z0-9-_]+\.[A-Za-z0-9-_]+\.?[A-Za-z0-9-_]*$/', $id_token))) {
      return new JsonResponse(['errors' => 'Formato de token inválido'], Response::HTTP_BAD_REQUEST);
    }

    $client = new Google_Client(['client_id' => $_ENV["GOOGLE_CLIENT_ID"]]);
    $payload = $client->verifyIdToken($id_token);
    if ($payload) {
      if ($payload['aud'] == $_ENV["GOOGLE_CLIENT_ID"]) {
        $userid = $payload['sub'];
        $usuario = $this->em->getRepository(Usuario::class)->findOneBy(['googleid' => $userid]);
        if (is_null($usuario)) {
          //register
        }
        $oauthClient = $usuario->getOauthClient();
      } else {
        return new JsonResponse(['errors' => 'Token inválido'], Response::HTTP_BAD_REQUEST);
      }
    } else {
      return new JsonResponse(['errors' => 'Token inválido'], Response::HTTP_BAD_REQUEST);
    }
    
    // build a standard client credentials request
    $request->$property->set('client_id', $oauthClient->getPublicId());
    $request->$property->set('client_secret', $oauthClient->getSecret());
    $request->$property->set('grant_type', OAuth2::GRANT_TYPE_CLIENT_CREDENTIALS);
    //$request->$property->set('scope', 'widget');

    $response = parent::tokenAction($request);
    if ($response->getStatusCode(Response::HTTP_OK)) {
      return $response;
    } else {
      return new JsonResponse(['errors' => 'Credenciales inválidas o faltantes'], Response::HTTP_BAD_REQUEST);
    }
  }
}
