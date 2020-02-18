<?php

namespace App\Controller\Tests;

use App\Entity\Dominio;
use App\Entity\Usuario;
use App\Test\ApiTestCase;
use Doctrine\Persistence\ObjectManager;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\Response;

class DominioControllerTest extends ApiTestCase
{
    private static $dominioName = "Test";
    private static $resourceUri;
    private static $publicResourceUri;
    private static $autorEmail = "autor@test.com";
    private static $usuarioEmail = "usuario@test.com";

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$resourceUri = self::$prefijo_api . "/dominios";
        self::$publicResourceUri = self::$prefijo_api . "/public/dominios";
        $usuario = self::createAutor(self::$autorEmail);
        self::$access_token = self::getNewAccessToken($usuario);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        /** @var ObjectManager $em */
        $em = self::getService("doctrine")->getManager();
        $dominio = $em->getRepository(Dominio::class)->findOneBy(["nombre" => self::$dominioName]);
        if (!is_null($dominio)) {
            $em->remove($dominio);
            $em->flush();
        }
        self::removeUsuario(self::$usuarioEmail);
    }

    public static function tearDownAfterClass(): void
    {
        /** @var ObjectManager $em */
        self::removeUsuario(self::$autorEmail);
    }

    private function createDominio(?string $nombre = null): int
    {
        /** @var ObjectManager $em */
        $em = self::getService("doctrine")->getManager();
        $dominio = new Dominio();
        $dominio->setNombre(is_null($nombre) ? self::$dominioName : $nombre);
        $em->persist($dominio);
        $em->flush();
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
        $data = json_decode((string) $response->getBody(), true);
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
            self::assertErrorResponse($e->getResponse(), Response::HTTP_BAD_REQUEST);

            /** @var ObjectManager $em */
            $em = self::getService("doctrine")->getManager();
            $dominios = $em->getRepository(Dominio::class)->findBy(["nombre" => self::$dominioName]);
            $this->assertEquals(1, count($dominios));
        }
    }

    public function testPostUnauthorized()
    {
        $this->assertUnauthorized(self::POST, self::$resourceUri);
    }

    public function testPostForbidden()
    {
        $usuario = self::createUsuarioApp(self::$usuarioEmail);
        $access_token = self::getNewAccessToken($usuario);
        $this->assertForbidden(self::POST, self::$resourceUri, $access_token);
    }

    public function testPostWrongToken()
    {
        $this->assertWrongToken(self::POST, self::$resourceUri);
    }

    public function testPostNoJson()
    {
        $options = [
            'headers' => ['Authorization' => 'Bearer ' . self::$access_token]
        ];
        try {
            self::$client->post(self::$resourceUri, $options);
            $this->fail("No se detectó que no hay json en el request");
        } catch (RequestException $e) {
            self::assertErrorResponse($e->getResponse(), Response::HTTP_BAD_REQUEST);
        }
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
            self::assertErrorResponse($e->getResponse(), Response::HTTP_BAD_REQUEST);
        }
    }
}
