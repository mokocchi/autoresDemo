<?php

namespace App\Tests\Controller;

use App\Test\ApiTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MeControllerTest extends ApiTestCase
{
    private static $autorEmail = "autor@test.com";

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$resourceUri = self::$prefijo_api . "/me";
        $usuario = self::createAutor(self::$autorEmail);
        self::$access_token = self::getNewAccessToken($usuario);
    }

    public static function tearDownAfterClass(): void
    {
        self::removeUsuarios();
    }

    public function testMe() {
        $response = self::$client->get(self::$resourceUri, $this->getDefaultOptions());
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $data = $this->getJson($response);
        $this->assertEquals([
            "nombre", 
            "apellido", 
            "email",
            "googleid",
            "roles"
        ], array_keys($data));
        $this->assertEquals("Pedro", $data["nombre"]);
        $this->assertEquals("Sánchez", $data["apellido"]);
        $this->assertEquals("autor@test.com", $data["email"]);
        $this->assertEquals("2000", $data["googleid"]);
        $this->assertEquals("ROLE_AUTOR", $data["roles"][0]["name"]);
    }

    public function testMeUnauthorized()
    {
        $this->assertUnauthorized(Request::METHOD_GET, self::$resourceUri);
    }

    public function testMeWrongToken()
    {
        $this->assertWrongToken(Request::METHOD_GET, self::$resourceUri);
    }
}
