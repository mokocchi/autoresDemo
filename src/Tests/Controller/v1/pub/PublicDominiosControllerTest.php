<?php

namespace App\Test\Controller\v1\pub;

use App\Entity\Dominio;
use App\Test\ApiTestCase;

class PublicDominiosControllerTest extends ApiTestCase
{

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$resourceUri = self::$prefijo_api . "/public/dominios";
    }

    public function tearDown(): void
    {
        parent::tearDown();
        self::truncateEntities([Dominio::class]);
    }

    public function testGetAll()
    {
        for ($i=0; $i < 25; $i++) { 
            $this->createDominio("Test" . $i);
        }
        $this->createDominio("wontmach");

        $uri = self::$resourceUri . "?nombre=Test";
        $response = self::$client->get($uri);
        $data = $this->getJson($response);
        $this->assertEquals(25, count($data["results"]));
        $this->assertEquals("Test11", $data["results"][11]["nombre"]);
    }
}