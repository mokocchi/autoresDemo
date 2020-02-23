<?php

namespace App\Test\Controller\v1\pub;

use App\Entity\Idioma;
use App\Test\ApiTestCase;

class PublicIdiomasControllerTest extends ApiTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$resourceUri = self::$prefijo_api . "/public/idiomas";
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $repository = self::$em->getRepository(Idioma::class);
        $repository->deleteLike("Idioma");
        self::$em->flush();
    }

    public function testGetAll()
    {
        for ($i = 0; $i < 25; $i++) {
            $idioma = new Idioma();
            $idioma->setNombre("Idioma" . $i);
            $idioma->setCode($i);
            self::$em->persist($idioma);
        }
        self::$em->flush();

        $response = self::$client->get(self::$resourceUri);
        $data = $this->getJson($response);
        $this->assertEquals(28, count($data["results"]));
        $this->assertEquals("Idioma8", $data["results"][11]["nombre"]);
    }
}
