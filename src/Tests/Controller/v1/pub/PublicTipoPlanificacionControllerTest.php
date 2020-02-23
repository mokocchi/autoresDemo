<?php

namespace App\Test\Controller\v1\pub;

use App\Entity\TipoPlanificacion;
use App\Test\ApiTestCase;

class PublicTipoPlanificacionControllerTest extends ApiTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$resourceUri = self::$prefijo_api . "/public/tipos-planificacion";
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $repository = self::$em->getRepository(TipoPlanificacion::class);
        $repository->deleteLike("Tipo Planificación");
        self::$em->flush();
    }

    public function testGetAll()
    {
        for ($i = 0; $i < 25; $i++) {
            $estado = new TipoPlanificacion();
            $estado->setNombre("Tipo Planificación" . $i);
            self::$em->persist($estado);
        }
        self::$em->flush();

        $response = self::$client->get(self::$resourceUri);
        $data = $this->getJson($response);
        $this->assertEquals(28, count($data["results"]));
        $this->assertEquals("Tipo Planificación8", $data["results"][11]["nombre"]);
    }
}
