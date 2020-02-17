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

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$resourceUri = self::$prefijo_api . "/dominios";
        $usuario = self::createAutor();
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
        $usuario = $em->getRepository(Usuario::class)->findOneBy(["email" => "usuario@test.com"]);
        self::removeUsuario($usuario);
    }

    public static function tearDownAfterClass(): void
    {
        /** @var ObjectManager $em */
        $em = self::getService("doctrine")->getManager();
        $autor = $em->getRepository(Usuario::class)->findOneBy(["email" => "autor@test.com"]);
        self::removeUsuario($autor);
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
        } catch (RequestException $e) {
            $this->assertEquals(Response::HTTP_BAD_REQUEST, $e->getResponse()->getStatusCode());
            $data = json_decode((string) $e->getResponse()->getBody(), true);
            $this->assertEquals([
                "status",
                "developer_message",
                "user_message",
                "error_code",
                "more_info"
            ], array_keys($data));

            /** @var ObjectManager $em */
            $em = self::getService("doctrine")->getManager();
            $dominios = $em->getRepository(Dominio::class)->findBy(["nombre" => self::$dominioName]);
            $this->assertEquals(1, count($dominios));
        }
    }

    public function testPostUnauthorized()
    {
        try {
            self::$client->post(self::$resourceUri);
        } catch (RequestException $e) {
            $this->assertEquals(Response::HTTP_UNAUTHORIZED, $e->getResponse()->getStatusCode());
            $data = json_decode((string) $e->getResponse()->getBody(), true);
            $this->assertEquals([
                "status",
                "developer_message",
                "user_message",
                "error_code",
                "more_info"
            ], array_keys($data));
        }
    }

    public function testPostForbidden()
    {
        $usuario = self::createUsuarioApp();
        $access_token = self::getNewAccessToken($usuario);

        $options = [
            "headers" => ["Authorization" => "Bearer " . $access_token],
            "json" => [
                "nombre" => self::$dominioName
            ]
        ];
        try {
            self::$client->post(self::$resourceUri, $options);
        } catch (RequestException $e) {
            $this->assertEquals(Response::HTTP_FORBIDDEN, $e->getResponse()->getStatusCode());
            $data = json_decode((string) $e->getResponse()->getBody(), true);
            $this->assertEquals([
                "status",
                "developer_message",
                "user_message",
                "error_code",
                "more_info"
            ], array_keys($data));
        }
    }

    public function testPostWrongToken()
    {
        $options = [
            "headers" => ["Authorization" => "Bearer %token%"],
            "json" => [
                "nombre" => self::$dominioName
            ]
        ];
        try {
            self::$client->post(self::$resourceUri, $options);
        } catch (RequestException $e) {
            $data = json_decode((string) $e->getResponse()->getBody(), true);
            $this->assertEquals(Response::HTTP_UNAUTHORIZED, $e->getResponse()->getStatusCode());
            $this->assertEquals([
                "status",
                "developer_message",
                "user_message",
                "error_code",
                "more_info"
            ], array_keys($data));
        }
    }
}
