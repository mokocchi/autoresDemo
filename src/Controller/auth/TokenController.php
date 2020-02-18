<?php

namespace App\Controller\auth;

use App\ApiProblem;
use App\Entity\AccessToken;
use App\Entity\Client;
use App\Entity\Role;
use App\Entity\Usuario;
use Doctrine\ORM\EntityManagerInterface;
use FOS\OAuthServerBundle\Controller\TokenController as BaseTokenController;
use FOS\OAuthServerBundle\Model\ClientManagerInterface;
use FOS\RestBundle\Controller\Annotations\Route;
use Google_Client;
use OAuth2\OAuth2;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\Serializer\SerializerInterface;
use Swagger\Annotations as SWG;

/**
 * @Route("/oauth/v2/token")
 */
class TokenController extends BaseTokenController
{
  protected $clientManager;
  protected $tokenManager;
  protected $em;
  protected $serializer;

  public function __construct(OAuth2 $server, EntityManagerInterface $entityManager, ClientManagerInterface $clientManager, SerializerInterface $serializer)
  {
    parent::__construct($server);
    $this->em = $entityManager;
    $this->clientManager = $clientManager;
    $this->serializer = $serializer;
  }

  private function register($client, $userid, $id_token)
  {
    $httpClient = $client->authorize();

    // make an HTTP request
    $response = $httpClient->get('https://www.googleapis.com/oauth2/v3/tokeninfo?id_token=' . $id_token);
    $data = json_decode((string) $response->getBody());
    $user = new Usuario();
    $user->setEmail($data->email);
    $user->setNombre($data->given_name);
    $user->setApellido($data->family_name);
    $user->setGoogleid($userid);
    $role = $this->em->getRepository(Role::class)->findOneBy(["name" => "ROLE_AUTOR"]);
    $user->addRole($role);

    $client = $this->clientManager->createClient();
    $client->setAllowedGrantTypes(array(OAuth2::GRANT_TYPE_CLIENT_CREDENTIALS));
    $user->setOAuthClient($client);
    $this->clientManager->updateClient($client);
    $this->em->persist($user);
    $this->em->flush();
    return $user;
  }

  /**
   * @Rest\Post(name="get_token")
   * 
   * @SWG\Response(
   *     response=200,
   *     description="The token was returned"
   * )
   *
   * @SWG\Response(
   *     response=400,
   *     description="There was a problem with the request"
   * )
   *
   * @SWG\Parameter(
   *     name="X-AUTH-TOKEN",
   *     in="header",
   *     type="string",
   *     description="Indicates a google id_token flow",
   * )
   *
   * @SWG\Parameter(
   *     name="X-AUTH-CREDENTIALS",
   *     in="header",
   *     type="string",
   *     description="Indicates a client_credentials flow",
   * )
   *
   * @SWG\Parameter(
   *     name="token",
   *     in="body",
   *     type="string",
   *     description="The google id_token from the client",
   *     schema={}
   * )
   *
   * @SWG\Parameter(
   *     name="client_id",
   *     in="formData",
   *     type="string",
   *     description="The OAuth client id"
   * )
   * 
   * * @SWG\Parameter(
   *     name="client_secret",
   *     in="formData",
   *     type="string",
   *     description="The OAuth client secret"
   * )
   *
   * @SWG\Tag(name="Auth")
   */
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
        $apiProblem = new ApiProblem(Response::HTTP_BAD_REQUEST, "No se encontró el header de autenticación", "Ocurrió un problema de autenticación");
        return new Response(
          $this->serializer->serialize($apiProblem, "json"),
          Response::HTTP_BAD_REQUEST
        );
      }
      $request->headers->remove('X-AUTH-CREDENTIALS');
      $request->$property->set('grant_type', OAuth2::GRANT_TYPE_CLIENT_CREDENTIALS);
      $response = parent::tokenAction($request);
      if ($response->getStatusCode() == Response::HTTP_OK) {
        $access_token = json_decode($response->getContent())->access_token;
        $token = $this->em->getRepository(AccessToken::class)->findOneBy(["token" => $access_token]);
        $id = explode("_", $request->$property->get("client_id"))[0];
        $client = $this->em->getRepository(Client::class)->find($id);
        $usuario = $this->em->getRepository(Usuario::class)->findOneBy(["oauthClient" => $client]);
        $token->setUser($usuario);
        $this->em->persist($token);
        $this->em->flush();
        return $response;
      } else {
        $apiProblem = new ApiProblem(Response::HTTP_BAD_REQUEST, "Credenciales inválidas o faltantes", "Ocurrió un error en la autenticación");
        return new Response(
          $this->serializer->serialize($apiProblem, "json"),
          Response::HTTP_BAD_REQUEST
        );
      }
    }

    $data = json_decode($request->getContent(), true);

    if(!array_key_exists('token', $data)) {
      $apiProblem = new ApiProblem(Response::HTTP_BAD_REQUEST, "No se encontró el id_token de usuario", "Ocurrió un error en la autenticación");
      return new Response(
        $this->serializer->serialize($apiProblem, "json"),
        Response::HTTP_BAD_REQUEST
      );
    }

    $id_token = $data['token'];
    $request->$property->remove('token');

    if (is_null($id_token)) {
      $apiProblem = new ApiProblem(Response::HTTP_BAD_REQUEST, "No se encontró el id_token de usuario", "Ocurrió un error en la autenticación");
      return new Response(
        $this->serializer->serialize($apiProblem, "json"),
        Response::HTTP_BAD_REQUEST
      );
    }

    if (!(preg_match('/^[A-Za-z0-9-_]+\.[A-Za-z0-9-_]+\.?[A-Za-z0-9-_]*$/', $id_token))) {
      $apiProblem = new ApiProblem(Response::HTTP_BAD_REQUEST, "El id_token no es un JWT válido", "Ocurrió un error en la autenticación");
      return new Response(
        $this->serializer->serialize($apiProblem, "json"),
        Response::HTTP_BAD_REQUEST
      );
    }

    $client = new Google_Client(['client_id' => $_ENV["GOOGLE_CLIENT_ID"]]);
    $payload = $client->verifyIdToken($id_token);
    $usuario = null;
    if ($payload) {
      if ($payload['aud'] == $_ENV["GOOGLE_CLIENT_ID"]) {
        $userid = $payload['sub'];
        $usuario = $this->em->getRepository(Usuario::class)->findOneBy(['googleid' => $userid]);
        if (is_null($usuario)) {
          $usuario = $this->register($client, $userid, $id_token);
        }
        $oauthClient = $usuario->getOauthClient();
      } else {
        $apiProblem = new ApiProblem(Response::HTTP_BAD_REQUEST, "El id_token no fue emitido para esta aplicación", "Ocurrió un error en la autenticación");
        return new Response(
          $this->serializer->serialize($apiProblem, "json"),
          Response::HTTP_BAD_REQUEST
        );
      }
    } else {
      $apiProblem = new ApiProblem(Response::HTTP_BAD_REQUEST, "El id_token no es válido", "Ocurrió un error con la autenticación");
      return new Response(
        $this->serializer->serialize($apiProblem, "json"),
        Response::HTTP_BAD_REQUEST
      );
    }

    // build a standard client credentials request
    $request->$property->set('client_id', $oauthClient->getPublicId());
    $request->$property->set('client_secret', $oauthClient->getSecret());
    $request->$property->set('grant_type', OAuth2::GRANT_TYPE_CLIENT_CREDENTIALS);
    //$request->$property->set('scope', 'widget');

    $response = parent::tokenAction($request);
    if ($response->getStatusCode(Response::HTTP_OK)) {
      $access_token = json_decode($response->getContent())->access_token;
      $token = $this->em->getRepository(AccessToken::class)->findOneBy(["token" => $access_token]);
      $token->setUser($usuario);
      $this->em->persist($token);
      $this->em->flush();
      return $response;
    } else {
      $apiProblem = new ApiProblem(Response::HTTP_BAD_REQUEST, "Credenciales inválidas o faltantes", "Ocurrió un error en la autenticación");
      return new Response(
        $this->serializer->serialize($apiProblem, "json"),
        Response::HTTP_BAD_REQUEST
      );
    }
  }
}
