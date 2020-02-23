<?php

namespace App\Controller\v1\pub;

use App\Entity\Actividad;
use App\Entity\Dominio;
use App\Entity\Planificacion;
use App\Test\ApiTestCase;

class PublicActividadesControllerTest extends ApiTestCase
{
    private static $autorEmail = "autor@test.com";
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $dominio = new Dominio();
        $dominio->setNombre(self::$dominioName);
        self::$em->persist($dominio);
        self::$em->flush();
        self::$dominioId = $dominio->getId();
        self::$resourceUri = self::$prefijo_api . "/public/actividades";
        $usuario = self::createAutor(self::$autorEmail);
        self::$access_token = self::getNewAccessToken($usuario);
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        self::truncateEntities([Actividad::class, Planificacion::class]);
        self::$em->clear();
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        self::truncateEntities([Dominio::class]);
        self::removeUsuarios();
    }
    /** @group getAll */
    public function testGetAll()
    {
        for ($i = 0; $i < 25; $i++) {
            $this->createActividad([
                "nombre" => "Actividad test",
                "codigo" => self::$actividadCodigo . $i,
                "objetivo" => "Probar la paginación de las actividades",
                "estado" => "Público"
            ]);
        }

        $this->createActividad([
            "nombre" => "Actividad not match",
            "codigo" => "codigo",
            "objetivo" => "Probar la paginación de las actividades",
            "estado" => "Público"
        ]);

        $this->createActividad([
            "nombre" => "Actividad test",
            "codigo" => "codigo",
            "objetivo" => "Probar la paginación de las actividades",
        ]);
        $uri = self::$resourceUri . '?filter=test';

        $response = self::$client->get($uri);
        $this->assertEquals(200, $response->getStatusCode());
        $data = $this->getJson($response);
        $this->assertEquals(self::$actividadCodigo . 5, $data["results"][5]["codigo"]);
        $this->assertEquals(10, $data["count"]);
        $this->assertEquals(25, $data["total"]);
        $this->assertArrayHasKey("_links", $data);
        $this->assertArrayHasKey("next", $data["_links"]);
        $nextLink = $data["_links"]["next"];
        $response = self::$client->get($nextLink);

        $this->assertEquals(200, $response->getStatusCode());
        $data = $this->getJson($response);
        $this->assertEquals(self::$actividadCodigo . 15, $data["results"][5]["codigo"]);
        $this->assertEquals(10, $data["count"]);
        $this->assertEquals(10, $data["count"]);

        $this->assertArrayHasKey("_links", $data);
        $this->assertArrayHasKey("last", $data["_links"]);
        $lastLink = $data["_links"]["last"];
        $response = self::$client->get($lastLink);
        $data = $this->getJson($response);
        $this->assertEquals(5, $data["count"]);
        $this->assertEquals(5, count($data["results"]));

        $response = self::$client->get($uri);
    }
}
