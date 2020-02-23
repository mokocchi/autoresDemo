<?php

namespace App\Test\Controller\v1\pub;

use App\Entity\TipoTarea;
use App\Test\ApiTestCase;

class PublicTipoTareaControllerTest extends ApiTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$resourceUri = self::$prefijo_api . "/public/tipos-tarea";
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $repository = self::$em->getRepository(TipoTarea::class);
        $repository->deleteLike("Tipo Tarea");
        self::$em->flush();
    }

    public function testGetAll()
    {
        for ($i = 0; $i < 25; $i++) {
            $tipo = new TipoTarea();
            $tipo->setNombre("Tipo Tarea" . $i);
            $tipo->setCodigo("tipo" . $i);
            self::$em->persist($tipo);
        }
        self::$em->flush();

        $response = self::$client->get(self::$resourceUri);
        $data = $this->getJson($response);
        $this->assertEquals(35, count($data["results"]));
        $this->assertEquals("Tipo Tarea1", $data["results"][11]["nombre"]);
    }
}
