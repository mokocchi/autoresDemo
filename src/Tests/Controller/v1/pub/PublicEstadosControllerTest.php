<?php

namespace App\Test\Controller\v1\pub;

use App\Entity\Estado;
use App\Repository\EstadoRepository;
use App\Test\ApiTestCase;

class PublicEstadosControllerTest extends ApiTestCase
{

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$resourceUri = self::$prefijo_api . "/public/estados";
    }

    public function tearDown(): void
    {
        parent::tearDown();
        /** @var EstadoRepository $repository */
        $repository = self::$em->getRepository(Estado::class);
        $repository->deleteLike("Estado");        
        self::$em->flush();
    }

    public function testGetAll()
    {
        for ($i = 0; $i < 25; $i++) {
            $estado = new Estado();
            $estado->setNombre("Estado" . $i);
            self::$em->persist($estado);
        }
        self::$em->flush();

        $response = self::$client->get(self::$resourceUri);
        $data = $this->getJson($response);
        $this->assertEquals(27, count($data["results"]));
        $this->assertEquals("Estado9", $data["results"][11]["nombre"]);
    }
}
