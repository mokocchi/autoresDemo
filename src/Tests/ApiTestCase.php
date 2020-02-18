<?php

namespace App\Test;

use App\Entity\AccessToken;
use App\Entity\Client as EntityClient;
use App\Entity\Role;
use App\Entity\Usuario;
use GuzzleHttp\Client;
use OAuth2\OAuth2;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
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
    protected static function getAuthHeader()
    {
        return 'Bearer ' . self::$access_token;
    }

    protected static function getDefaultOptions()
    {
        return ["headers" => ['Authorization' => self::getAuthHeader()]];
    }

    protected function assertApiProblemResponse($response)
    {
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals(self::$apiProblemArray, array_keys($data));
    }

    protected function assertErrorResponse($response, $statusCode)
    {
        $this->assertEquals($statusCode, $response->getStatusCode());
        $this->assertApiProblemResponse($response);
    }

    protected static function createAutor()
    {
        return self::createUsuario([
            "email" => "autor@test.com",
            "nombre" => "Pedro",
            "apellido" => "Sánchez",
            "googleid" => "2000",
            "role" => "ROLE_AUTOR"
        ]);
    }

    protected static function createUsuarioApp()
    {
        return self::createUsuario([
            "email" => "usuario@test.com",
            "nombre" => "María",
            "apellido" => "Del Carril",
            "googleid" => "3000",
            "role" => "ROLE_USUARIO_APP"
        ]);
    }

    protected static function createUsuario(array $usuarioArray)
    {
        /** @var ObjectManager $em */
        $em = self::getService('doctrine')->getManager();
        $user = new Usuario();
        $user->setEmail($usuarioArray["email"]);
        $user->setNombre($usuarioArray["nombre"]);
        $user->setApellido($usuarioArray["apellido"]);
        $user->setGoogleid($usuarioArray["googleid"]);
        $role = $em->getRepository(Role::class)->findOneBy(["name" => $usuarioArray["role"]]);
        $user->addRole($role);

        $client = new EntityClient();
        $client->setAllowedGrantTypes(array(OAuth2::GRANT_TYPE_CLIENT_CREDENTIALS));
        $user->setOAuthClient($client);
        $em->persist($client);
        $em->flush();
        $em->persist($user);
        $em->flush();
        return $user;
    }

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
    }

    protected static function removeUsuario($usuario)
    {
        $em = self::getService('doctrine')->getManager();
        if (!is_null($usuario)) {
            $access_tokens = $em->getRepository(AccessToken::class)->findBy(["user" => $usuario->getId()]);
            foreach ($access_tokens as $token) {
                $em->remove($token);
            }
            $em->flush();
            $em->remove($usuario);
            $em->flush();
        }
    }

    protected function tearDown(): void
    {
    }

    protected static function getService($id)
    {
        return self::$kernel->getContainer()->get($id);
    }
}
