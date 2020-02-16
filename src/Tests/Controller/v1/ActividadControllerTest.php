<?php

declare(strict_types=1);

namespace App\Controller\Tests;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\Response;

class ActividadControllerTest extends \PHPUnit\Framework\TestCase
{
    private $access_token;
    private $client;
    private $prefijo_api = '/api/v1.0';
    private $dominioId;

    private function getAuthHeader()
    {
        return 'Bearer ' . $this->access_token;
    }

    public function setUp()
    {
        $this->client = new Client(
            [
                'base_uri' => 'http://localhost:8080/'
            ]
        );
        $options = [
            'headers' => ['X-AUTH-CREDENTIALS' => true],
            'form_params' => [
            ]
        ];
        $response = $this->client->post('/api/oauth/v2/token', $options);

        $data = json_decode((string) $response->getBody());
        $this->access_token = $data->access_token;

        $uri = $this->prefijo_api . "/dominios";
        $options = [
            'headers' => ['Authorization' => $this->getAuthHeader()],
            'json' => [
                "nombre" => "Test",
            ]
        ];

        $response = $this->client->post($uri, $options);
        $data = json_decode((string) $response->getBody(), true);
        
        $this->dominioId = $data["id"];
    }

    public function tearDown() {
        $uri = $this->prefijo_api . "/actividades?codigo=actividadtest";
        $options = [
            "headers" => [ "Authorization" => $this->getAuthHeader()]
        ];

        $response = $this->client->get($uri, $options);
        $data = json_decode((string) $response->getBody(), true);
        
        $actividades = $data["results"];

        foreach ($actividades as $actividad) {
            $uri = $this->prefijo_api . "/actividades/" . $actividad["id"];
            $this->client->delete($uri, $options);
        }

        $uri = $this->prefijo_api . "/dominios/" . $this->dominioId;
        
        $this->client->delete($uri, $options);
    }

    public function testPost()
    {
        $uri = $this->prefijo_api . "/actividades";

        $options = [
            'headers' => ['Authorization' => $this->getAuthHeader()],
            'json' => [
                "nombre" => "Actividad test",
                "objetivo" => "Probar crear una actividad",
                "codigo" => "actividadtest",
                "dominio" => $this->dominioId,
                "idioma" => 1,
                "tipoPlanificacion" => 1,
                "estado" => 1
            ]
        ];

        $response = $this->client->post($uri, $options);
        $this->assertTrue($response->hasHeader("Location"));
        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey("nombre", $data);
    }


    public function testPostMissingFields()
    {
        $uri = $this->prefijo_api . "/actividades";

        $options = [
            'headers' => ['Authorization' => $this->getAuthHeader()],
            'json' => [
                "objetivo" => "Probar crear una actividad",
                "dominio" => $this->dominioId,
                "codigo" => "actividadtest",
                "idioma" => 1,
                "tipoPlanificacion" => 1,
                "estado" => 1
            ]
        ];

        try {
            $this->client->post($uri, $options);
        } catch (RequestException $e) {
            $this->assertTrue($e->getResponse()->getStatusCode() == Response::HTTP_BAD_REQUEST);
        }
    }

    public function testPut()
    {
        $uri = $this->prefijo_api . "/actividades";

        $options = [
            'headers' => ['Authorization' => $this->getAuthHeader()],
            'json' => [
                "nombre" => "Actividad test",
                "objetivo" => "Probar crear una actividad",
                "codigo" => "actividadtest",
                "dominio" => $this->dominioId,
                "idioma" => 1,
                "tipoPlanificacion" => 1,
                "estado" => 1
            ]
        ];

        $response = $this->client->post($uri, $options);
        $data = json_decode((string) $response->getBody(), true);
        
        $id = $data["id"];

        $uri = $this->prefijo_api . "/actividades/" . $id; //TODO: id por codigos

        $options = [
            'headers' => ['Authorization' => $this->getAuthHeader()],
            'json' => [
                "nombre" => "Actividad test 2"
            ]
        ];

        $response = $this->client->put($uri, $options);
        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey("nombre", $data);
        $this->assertTrue($data["nombre"] == "Actividad test 2");
    }

    public function testPutMissingJson() {
        $uri = $this->prefijo_api . "/actividades";

        $options = [
            'headers' => ['Authorization' => $this->getAuthHeader()],
            'json' => [
                "nombre" => "Actividad test",
                "objetivo" => "Probar crear una actividad",
                "codigo" => "actividadTest",
                "dominio" => $this->dominioId,
                "idioma" => 1,
                "tipoPlanificacion" => 1,
                "estado" => 1
            ]
        ];

        $response = $this->client->post($uri, $options);
        $data = json_decode((string) $response->getBody(), true);
        $id = $data["id"];

        $uri = $this->prefijo_api . "/actividades/" . $id; //TODO: id por codigos

        $options = [
            'headers' => ['Authorization' => $this->getAuthHeader()],
        ];

        try {
            $response = $this->client->put($uri, $options);
        } catch (RequestException $e) {
            $this->assertTrue($e->getResponse()->getStatusCode() == Response::HTTP_BAD_REQUEST);
        }
    }

    public function testDelete()
    {
        $uri = $this->prefijo_api . "/actividades";

        $options = [
            'headers' => ['Authorization' => $this->getAuthHeader()],
            'json' => [
                "nombre" => "Actividad test",
                "objetivo" => "Probar crear una actividad",
                "codigo" => "actividadTest",
                "dominio" => $this->dominioId,
                "idioma" => 1,
                "tipoPlanificacion" => 1,
                "estado" => 1
            ]
        ];

        $response = $this->client->post($uri, $options);
        $data = json_decode((string) $response->getBody(), true);
        $id = $data["id"];

        $options = ["headers" => ['Authorization' => $this->getAuthHeader()]];

        $uri = $this->prefijo_api . "/actividades/" . $id;

        $response = $this->client->delete($uri, $options);
        $this->assertTrue($response->getStatusCode() == Response::HTTP_NO_CONTENT);
    }
}
