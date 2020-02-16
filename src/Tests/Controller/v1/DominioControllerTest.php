<?php

namespace App\Controller\Tests;

use App\Test\ApiTestCase;

class DominioControllerTest extends ApiTestCase
{
    public function tearDown()
    {
        $uri = self::$prefijo_api . "/public/dominios?nombre=Test";
        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()]
        ];
        $response = self::$client->get($uri, $options);
        $data = json_decode((string) $response->getBody(), true);

        $dominios = $data["results"];

        foreach ($dominios as $dominio) {
            $uri = self::$prefijo_api . "/dominios/" . $dominio["id"];
            self::$client->delete($uri, $options);
        }
    }

    public function testpost()
    {
        $uri = self::$prefijo_api . "/dominios";

        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
            'json' => [
                "nombre" => "Test",
            ]
        ];

        $response = self::$client->post($uri, $options);
        $this->assertTrue($response->hasHeader("Location"));
        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey("nombre", $data);
    }
}
