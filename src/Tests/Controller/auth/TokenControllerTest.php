<?php

namespace App\Test\Controller\auth;

use App\Entity\Usuario;
use App\Test\ApiTestCase;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\Response;

class TokenControllerTest extends ApiTestCase
{
    private static $token_uri = '/api/oauth/v2/token';
    private static $usuario;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$usuario = self::createUsuario([
            "email" => "carlos@test.com",
            "nombre" => "Carlos",
            "apellido" => "Eco",
            "googleid" => 2001,
            "role" => "ROLE_AUTOR"
        ]);
    }
    public function tearDown(): void
    {
        parent::tearDown();
        /** @var ObjectManager $em */
        $em = self::getService('doctrine')->getManager();
        $autor = $em->getRepository(Usuario::class)->findOneBy(["email" => "carlos@test.com"]);
        self::removeUsuario($autor);
    }

    /** @group failing */
    public function testTokenAction()
    {
        $client = self::$usuario->getOauthClient();
        $client_id = $client->getPublicId();
        $secret = $client->getSecret();

        $options = [
            'headers' => ['X-AUTH-CREDENTIALS' => true],
            'form_params' => [
                'client_id' => $client_id,
                'client_secret' => $secret
            ]
        ];
        $response = self::$client->post(self::$token_uri, $options);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        dd((string) $response->getBody());
        $data = json_decode((string) $response->getBody(), true);
        if(is_null($data)) {
            $data = [];
        }
        $this->assertEquals(
            [
                "access_token",
                "expires_in",
                "token_type",
                "scope"
            ],
            array_keys($data)
        );
        $this->assertEquals(3600, (int) $data["expires_in"]);
        $this->assertEquals("bearer", $data["token_type"]);
        $this->assertEquals("api_client", $data["scope"]);
    }


    public function testTokenActionWithoutHeader()
    {
        try {
            self::$client->post(self::$token_uri);
            $this->fail("No se detectó el header faltante");
        } catch (RequestException $e) {
            self::assertErrorResponse($e->getResponse(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function testTokenActionMissingCredentials()
    {
        $options = [
            'headers' => ['X-AUTH-CREDENTIALS' => true]
        ];
        try {
            self::$client->post(self::$token_uri, $options);
            $this->fail("No se detectó que no hay credenciales");
        } catch (RequestException $e) {
            self::assertErrorResponse($e->getResponse(), Response::HTTP_BAD_REQUEST);
        }
    }
    public function testTokenActionInvalidCredentials()
    {
        $options = [
            'headers' => ['X-AUTH-CREDENTIALS' => true],
            'json' => [
                'client_id' => '1234',
                'client_secret' => '5678'
            ]
        ];
        try {
            self::$client->post(self::$token_uri, $options);
            $this->fail("No se detectaron credenciales inválidas");
        } catch (RequestException $e) {
            self::assertErrorResponse($e->getResponse(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function testTokenActionIdTokenInvalidJson()
    {
        $invalidBody = <<<EOF
        {
            "id_token" "1234"
        }
        EOF;

        $options = [
            'headers' => ['X-AUTH-TOKEN' => true],
            'body' => $invalidBody
        ];

        try {
            self::$client->post(self::$token_uri, $options);
            $this->fail("No se detectó el JSON inválido");
        } catch (RequestException $e) {
            self::assertErrorResponse($e->getResponse(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function testTokenActionNoIdToken()
    {
        $options = [
            'headers' => ['X-AUTH-TOKEN' => true],
            'json' => [
            ]
        ];

        try {
            self::$client->post(self::$token_uri, $options);
            $this->fail("No se detectó el id_token faltante");
        } catch (RequestException $e) {
            self::assertErrorResponse($e->getResponse(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function testTokenActionNullToken()
    {
        $options = [
            'headers' => ['X-AUTH-TOKEN' => true],
            'json' => [
                'token' => null
            ]
        ];

        try {
            self::$client->post(self::$token_uri, $options);
            $this->fail("No se detectó el id_token null");
        } catch (RequestException $e) {
            self::assertErrorResponse($e->getResponse(), Response::HTTP_BAD_REQUEST);
        }   
    }

    public function testTokenActionInvalidIdTokenFormat()
    {
        $options = [
            'headers' => ['X-AUTH-TOKEN' => true],
            'json' => [
                'token' => '1234'
            ]
        ];

        try {
            self::$client->post(self::$token_uri, $options);
            $this->fail("No se detectó el formato erróneo de id_token");
        } catch (RequestException $e) {
            self::assertErrorResponse($e->getResponse(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function testTokenActionInvalidIdToken()
    {
        $options = [
            'headers' => ['X-AUTH-TOKEN' => true],
            'json' => [
                'token' => '1.2.3'
            ]
        ];

        try {
            self::$client->post(self::$token_uri, $options);
            $this->fail("No se detectó el id_token inválido");
        } catch (RequestException $e) {
            self::assertErrorResponse($e->getResponse(), Response::HTTP_BAD_REQUEST);
        }
    }
}
