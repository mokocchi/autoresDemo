<?php

namespace App\Controller\Tests;

use App\Entity\Dominio;
use App\Test\ApiTestCase;
use Doctrine\Persistence\ObjectManager;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DominioControllerTest extends ApiTestCase
{
    private static $dominioName = "Test";
    private static $resourceUri;
    private static $autorEmail = "autor@test.com";
    private static $usuarioEmail = "usuario@test.com";

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$resourceUri = self::$prefijo_api . "/dominios";
        $usuario = self::createAutor(self::$autorEmail);
        self::$access_token = self::getNewAccessToken($usuario);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        self::truncateEntities([Dominio::class]);
        self::removeUsuario(self::$usuarioEmail);
    }

    public static function tearDownAfterClass(): void
    {
        self::removeUsuarios();
    }

    private function createDominio(?string $nombre = null): int
    {
        $dominio = new Dominio();
        $dominio->setNombre(is_null($nombre) ? self::$dominioName : $nombre);
        self::$em->persist($dominio);
        self::$em->flush();
        return $dominio->getId();
    }

    public function testPostDominioAction()
    {
        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
            'json' => [
                "nombre" => self::$dominioName,
            ]
        ];

        $response = self::$client->post(self::$resourceUri, $options);
        $this->assertTrue($response->hasHeader("Location"));
        $data = $this->getJson($response);
        $this->assertArrayHasKey("nombre", $data);
        $this->assertEquals(self::$dominioName, $data["nombre"]);
    }

    public function testPostTwice()
    {
        $this->createDominio();

        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
            'json' => [
                "nombre" => self::$dominioName,
            ]
        ];

        try {
            self::$client->post(self::$resourceUri, $options);
            $this->fail("No se detectó el dominio repetido");
        } catch (RequestException $e) {
            self::assertErrorResponse($e->getResponse(), Response::HTTP_BAD_REQUEST, "Ya existe un dominio con el mismo nombre");
            $dominios = self::$em->getRepository(Dominio::class)->findBy(["nombre" => self::$dominioName]);
            $this->assertEquals(1, count($dominios));
        }
    }

    public function testPostUnauthorized()
    {
        $this->assertUnauthorized(Request::METHOD_POST, self::$resourceUri);
    }

    public function testPostForbidden()
    {
        $usuario = self::createUsuarioApp(self::$usuarioEmail);
        $access_token = self::getNewAccessToken($usuario);
        $this->assertForbidden(Request::METHOD_POST, self::$resourceUri, $access_token);
    }

    public function testPostWrongToken()
    {
        $this->assertWrongToken(Request::METHOD_POST, self::$resourceUri);
    }

    public function testPostNoJson()
    {
        $this->assertNoJson(Request::METHOD_POST, self::$resourceUri);
    }

    public function testPostNoNombre()
    {
        $options = [
            "headers" => ["Authorization" => "Bearer " . self::$access_token],
            "json" => []
        ];
        try {
            self::$client->post(self::$resourceUri, $options);
            $this->fail("No se detectó que no se envió un nombre");
        } catch (RequestException $e) {
            self::assertErrorResponse($e->getResponse(), Response::HTTP_BAD_REQUEST, "Uno o más de los campos requeridos falta o es nulo");
        }
    }
}
