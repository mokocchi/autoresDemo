<?php

declare(strict_types=1);

namespace App\Controller\Tests;

use App\Test\ApiTestCase;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\Response;

class ActividadControllerTest extends ApiTestCase
{
    private static $dominioId;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $uri = self::$prefijo_api . "/dominios";
        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
            'json' => [
                "nombre" => "Test",
            ]
        ];

        $response = self::$client->post($uri, $options);
        $data = json_decode((string) $response->getBody(), true);
        
        self::$dominioId = $data["id"];
    }

    public function tearDown() {
        $uri = self::$prefijo_api . "/actividades?codigo=actividadtest";
        $options = [
            "headers" => [ "Authorization" => self::getAuthHeader()]
        ];

        $response = self::$client->get($uri, $options);
        $data = json_decode((string) $response->getBody(), true);
        
        $actividades = $data["results"];

        foreach ($actividades as $actividad) {
            $uri = self::$prefijo_api . "/actividades/" . $actividad["id"];
            self::$client->delete($uri, $options);
        }
    }

    public static function tearDownAfterClass() {
        $uri = self::$prefijo_api . "/dominios/" . self::$dominioId;

        $options = [
            "headers" => [ "Authorization" => self::getAuthHeader()]
        ];

        self::$client->delete($uri, $options);
    }

    public function testPost()
    {
        $uri = self::$prefijo_api . "/actividades";

        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
            'json' => [
                "nombre" => "Actividad test",
                "objetivo" => "Probar crear una actividad",
                "codigo" => "actividadtest",
                "dominio" => self::$dominioId,
                "idioma" => 1,
                "tipoPlanificacion" => 1,
                "estado" => 1
            ]
        ];

        $response = self::$client->post($uri, $options);
        $this->assertTrue($response->hasHeader("Location"));
        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey("nombre", $data);
    }


    public function testPostMissingFields()
    {
        $uri = self::$prefijo_api . "/actividades";

        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
            'json' => [
                "objetivo" => "Probar crear una actividad",
                "dominio" => self::$dominioId,
                "codigo" => "actividadtest",
                "idioma" => 1,
                "tipoPlanificacion" => 1,
                "estado" => 1
            ]
        ];

        try {
            self::$client->post($uri, $options);
        } catch (RequestException $e) {
            $this->assertTrue($e->getResponse()->getStatusCode() == Response::HTTP_BAD_REQUEST);
        }
    }

    public function testPut()
    {
        $uri = self::$prefijo_api . "/actividades";

        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
            'json' => [
                "nombre" => "Actividad test",
                "objetivo" => "Probar crear una actividad",
                "codigo" => "actividadtest",
                "dominio" => self::$dominioId,
                "idioma" => 1,
                "tipoPlanificacion" => 1,
                "estado" => 1
            ]
        ];

        $response = self::$client->post($uri, $options);
        $data = json_decode((string) $response->getBody(), true);
        
        $id = $data["id"];

        $uri = self::$prefijo_api . "/actividades/" . $id; //TODO: id por codigos

        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
            'json' => [
                "nombre" => "Actividad test 2"
            ]
        ];

        $response = self::$client->put($uri, $options);
        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey("nombre", $data);
        $this->assertTrue($data["nombre"] == "Actividad test 2");
    }

    public function PutMissingJson() {
        $uri = self::$prefijo_api . "/actividades";

        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
            'json' => [
                "nombre" => "Actividad test",
                "objetivo" => "Probar crear una actividad",
                "codigo" => "actividadTest",
                "dominio" => self::$dominioId,
                "idioma" => 1,
                "tipoPlanificacion" => 1,
                "estado" => 1
            ]
        ];

        $response = self::$client->post($uri, $options);
        $data = json_decode((string) $response->getBody(), true);
        $id = $data["id"];

        $uri = self::$prefijo_api . "/actividades/" . $id; //TODO: id por codigos

        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
        ];

        try {
            $response = self::$client->put($uri, $options);
        } catch (RequestException $e) {
            $this->assertTrue($e->getResponse()->getStatusCode() == Response::HTTP_BAD_REQUEST);
        }
    }

    public function testDelete()
    {
        $uri = self::$prefijo_api . "/actividades";

        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
            'json' => [
                "nombre" => "Actividad test",
                "objetivo" => "Probar crear una actividad",
                "codigo" => "actividadTest",
                "dominio" => self::$dominioId,
                "idioma" => 1,
                "tipoPlanificacion" => 1,
                "estado" => 1
            ]
        ];

        $response = self::$client->post($uri, $options);
        $data = json_decode((string) $response->getBody(), true);
        $id = $data["id"];

        $options = ["headers" => ['Authorization' => self::getAuthHeader()]];

        $uri = self::$prefijo_api . "/actividades/" . $id;

        $response = self::$client->delete($uri, $options);
        $this->assertTrue($response->getStatusCode() == Response::HTTP_NO_CONTENT);
    }
}
