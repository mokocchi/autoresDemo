<?php

namespace App\Controller\Tests;

use App\Entity\Dominio;
use App\Test\ApiTestCase;
use Doctrine\Persistence\ObjectManager;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\Response;

class DominioControllerTest extends ApiTestCase
{
    private static $dominioName = "Test";

    protected function tearDown(): void
    {
        parent::tearDown();
        /** @var ObjectManager $em */
        $em = self::getService("doctrine")->getManager();
        $dominio = $em->getRepository(Dominio::class)->findOneBy(["nombre" => self::$dominioName]);
        if (!is_null($dominio)) {
            $em->remove($dominio);
            $em->flush();
        }
    }

    private function createDominio(?string $nombre=null): int
    {
        /** @var ObjectManager $em */
        $em = self::getService("doctrine")->getManager();
        $dominio = new Dominio();
        $dominio->setNombre(is_null($nombre) ? self::$dominioName : $nombre);
        $em->persist($dominio);
        $em->flush();
        return $dominio->getId();
    }

    public function testpost()
    {
        $uri = self::$prefijo_api . "/dominios";

        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
            'json' => [
                "nombre" => self::$dominioName,
            ]
        ];

        $response = self::$client->post($uri, $options);
        $this->assertTrue($response->hasHeader("Location"));
        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey("nombre", $data);
        $this->assertEquals(self::$dominioName, $data["nombre"]);
    }

    public function testPostTwice()
    {
        $this->createDominio();
        $uri = self::$prefijo_api . "/dominios";

        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
            'json' => [
                "nombre" => self::$dominioName,
            ]
        ];

        try {
            self::$client->post($uri, $options);
        } catch (RequestException $e) {
            $this->assertEquals(Response::HTTP_BAD_REQUEST, $e->getResponse()->getStatusCode());
            $data = json_decode((string) $e->getResponse()->getBody(), true);
            $this->assertEquals([
                "status",
                "developer_message",
                "user_message",
                "error_code",
                "more_info"
            ],array_keys($data));
        }
    }
}