<?php

namespace App\Test;

use App\Entity\AccessToken;
use App\Entity\Client as EntityClient;
use App\Entity\Role;
use App\Entity\Usuario;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use OAuth2\OAuth2;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTestCase extends KernelTestCase
{
    protected static $client;
    protected static $access_token;
    protected static $prefijo_api = '/api/v1.0';
    protected static $apiProblemArray = [
        "status",
        "developer_message",
        "user_message",
        "error_code",
        "more_info"
    ];
    protected static $em;

    protected static function getAuthHeader()
    {
        return 'Bearer ' . self::$access_token;
    }

    protected static function getDefaultOptions()
    {
        return ["headers" => ['Authorization' => self::getAuthHeader()]];
    }

    protected function assertApiProblemResponse($response, $message)
    {
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals(self::$apiProblemArray, array_keys($data));
        $this->assertEquals($message, $data["developer_message"]);
    }

    protected function assertErrorResponse($response, $statusCode, $message)
    {
        $this->assertEquals($statusCode, $response->getStatusCode());
        $this->assertApiProblemResponse($response, $message);
    }

    protected function dumpError(RequestException $e)
    {
        $data = json_decode((string) $e->getResponse()->getBody(), true);
        dd($data["user_message"]);
    }

    protected static function createAutor(string $email)
    {
        return self::createUsuario([
            "email" => $email,
            "nombre" => "Pedro",
            "apellido" => "Sánchez",
            "googleid" => "2000",
            "role" => "ROLE_AUTOR"
        ]);
    }

    protected static function createUsuarioApp($email)
    {
        return self::createUsuario([
            "email" => "$email",
            "nombre" => "María",
            "apellido" => "Del Carril",
            "googleid" => "3000",
            "role" => "ROLE_USUARIO_APP"
        ]);
    }

    protected static function createUsuario(array $usuarioArray)
    {
        $user = new Usuario();
        $user->setEmail($usuarioArray["email"]);
        $user->setNombre($usuarioArray["nombre"]);
        $user->setApellido($usuarioArray["apellido"]);
        $user->setGoogleid($usuarioArray["googleid"]);
        $role = self::$em->getRepository(Role::class)->findOneBy(["name" => $usuarioArray["role"]]);
        $user->addRole($role);

        $client = new EntityClient();
        $client->setAllowedGrantTypes(array(OAuth2::GRANT_TYPE_CLIENT_CREDENTIALS));
        $user->setOAuthClient($client);
        self::$em->persist($client);
        self::$em->flush();
        self::$em->persist($user);
        self::$em->flush();
        return $user;
    }

    /** @return AccessToken  */
    protected static function getNewAccessToken(Usuario $usuario)
    {
        $client = $usuario->getOauthClient();
        $client_id = $client->getPublicId();
        $secret = $client->getSecret();

        $options = [
            'headers' => ['X-AUTH-CREDENTIALS' => true],
            'form_params' => [
                'client_id' => $client_id,
                'client_secret' => $secret
            ]
        ];
        $response = self::$client->post('/api/oauth/v2/token', $options);

        $data = json_decode((string) $response->getBody());
        return $data->access_token;
    }

    public static function setUpBeforeClass(): void
    {
        self::bootKernel();
        self::$client = new Client(
            [
                'base_uri' => 'http://localhost:80/'
            ]
        );
        self::$em = self::getService("doctrine")->getManager();
    }

    protected static function removeUsuario($email)
    {
        $usuario = self::$em->getRepository(Usuario::class)->findOneBy(["email" => $email]);
        if (!is_null($usuario)) {
            $access_tokens = self::$em->getRepository(AccessToken::class)->findBy(["user" => $usuario->getId()]);
            foreach ($access_tokens as $token) {
                self::$em->remove($token);
            }
            self::$em->flush();
            self::$em->remove($usuario);
            self::$em->flush();
            $client = $usuario->getOauthClient();
            self::$em->remove($client);
            self::$em->flush();
        }
    }

    protected function tearDown(): void
    {
    }

    protected static function getService($id)
    {
        return self::$kernel->getContainer()->get($id);
    }

    protected function assertUnauthorized($method, $uri)
    {
        try {
            switch ($method) {
                case Request::METHOD_GET:
                    self::$client->get($uri);
                    break;
                case Request::METHOD_POST:
                    self::$client->post($uri);
                    break;
                case Request::METHOD_PATCH:
                    self::$client->patch($uri);
                    break;
                case Request::METHOD_DELETE:
                    self::$client->delete($uri);
                default:
                    break;
            }
        } catch (RequestException $e) {
            self::assertErrorResponse($e->getResponse(), Response::HTTP_UNAUTHORIZED, 'Se requiere autenticación OAuth');
        }
    }

    protected function assertForbidden($method, $uri, $access_token)
    {
        $options = [
            "headers" => ["Authorization" => "Bearer " . $access_token]
        ];
        try {
            switch ($method) {
                case Request::METHOD_GET:
                    self::$client->get($uri, $options);
                    break;
                case Request::METHOD_POST:
                    self::$client->post($uri, $options);
                    break;
                case Request::METHOD_PATCH:
                    self::$client->patch($uri, $options);
                    break;
                case Request::METHOD_DELETE:
                    self::$client->delete($uri, $options);
                default:
                    break;
            }
        } catch (RequestException $e) {
            self::assertErrorResponse($e->getResponse(), Response::HTTP_FORBIDDEN, "No tenés los permisos suficientes para acceder al recurso");
        }
    }

    protected function assertWrongToken($method, $uri)
    {
        $options = [
            "headers" => ["Authorization" => "Bearer %token%"]
        ];
        try {
            switch ($method) {
                case Request::METHOD_GET:
                    self::$client->get($uri, $options);
                    break;
                case Request::METHOD_POST:
                    self::$client->post($uri, $options);
                    break;
                case Request::METHOD_PATCH:
                    self::$client->patch($uri, $options);
                    break;
                case Request::METHOD_DELETE:
                    self::$client->delete($uri, $options);
                default:
                    break;
            }
        } catch (RequestException $e) {
            self::assertErrorResponse($e->getResponse(), Response::HTTP_UNAUTHORIZED, "El token expiró o es inválido");
        }
    }

    protected function assertNoJson($method, $uri)
    {
        $options = [
            'headers' => ['Authorization' => 'Bearer ' . self::$access_token]
        ];
        try {
            switch ($method) {
                case Request::METHOD_POST:
                    self::$client->post($uri, $options);
                    break;
                case Request::METHOD_PATCH:
                    self::$client->patch($uri, $options);
                    break;
                default:
                    break;
            }
            $this->fail("No se detectó que no hay json en el request");
        } catch (RequestException $e) {
            self::assertErrorResponse($e->getResponse(), Response::HTTP_BAD_REQUEST, "No hay campos en el json");
        }
    }

    public function assertNotFound($method, $uri, $className)
    {
        try {
            switch ($method) {
                case Request::METHOD_GET:
                    self::$client->get($uri, self::getDefaultOptions());
                case Request::METHOD_PATCH:
                    $options = [
                        "headers" => ["Authorization" => self::getAuthHeader()],
                        "json" => []
                    ];
                    self::$client->patch($uri, $options);
                    break;
                case Request::METHOD_DELETE:
                    self::$client->delete($uri, self::getDefaultOptions());
                    break;
                default:
                    break;
            }
        } catch (RequestException $e) {
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_NOT_FOUND, sprintf("No se encontró: %s", $className));
        }
    }
}
