<?php

namespace App\Controller\Tests;

use App\Entity\Dominio;
use App\Test\ApiTestCase;
use Doctrine\Persistence\ObjectManager;

class DominioControllerTest extends ApiTestCase
{
    private static $dominioName = "Test";

    protected function tearDown(): void
    {
        parent::tearDown();
        /** @var ObjectManager $em */
        $em = self::getService("doctrine")->getManager();
        $dominio = $em->getRepository(Dominio::class)->findOneBy(["nombre" => self::$dominioName]);
        if(!is_null($dominio)){
            $em->remove($dominio);
            $em->flush();
        }
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
}
