<?php

declare(strict_types=1);

namespace App\Controller\Tests;

use GuzzleHttp\Client;

class ActividadControllerTest extends \PHPUnit\Framework\TestCase
{
    private $access_token;
    public function setUp()
    {
        $client = new Client(
            [
                'base_uri' => 'http://localhost:8080/'
            ]
        );
        $options = [
            'headers' => ['X-AUTH-CREDENTIALS' => true],
            'form_params' => [
            ]
        ];
        $response = $client->post('/api/oauth/v2/token', $options);

        $data = json_decode((string) $response->getBody());
        $this->access_token = $data->access_token;
    }
    public function testPost()
    {
        $client = new Client(
            [
                'base_uri' => 'http://localhost:8080/'
            ]
        );
        $auth_header = 'Bearer ' . $this->access_token;

        $prefijo_api = '/api/v1.0';

        $uri = $prefijo_api . "/actividades";

        $options = [
            'headers' => ['Authorization' => $auth_header],
            'json' => [
                "nombre" => "Actividad test",
                "objetivo" => "Probar crear una actividad",
                "dominio" => 33,
                "idioma" => 1,
                "tipoPlanificacion" => 1,
                "estado" => 1
            ]
        ];

        $response = $client->post($uri, $options);
        $this->assertTrue($response->hasHeader("Location"));
        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey("nombre", $data);
    }

    public function testPut()
    {
        $client = new Client(
            [
                'base_uri' => 'http://localhost:8080/'
            ]
        );
        $auth_header = 'Bearer ' . $this->access_token;

        $prefijo_api = '/api/v1.0';

        $uri = $prefijo_api . "/actividades";

        $options = [
            'headers' => ['Authorization' => $auth_header],
            'json' => [
                "nombre" => "Actividad test 2",
                "objetivo" => "Probar crear una actividad",
                "dominio" => 33,
                "idioma" => 1,
                "tipoPlanificacion" => 1,
                "estado" => 1
            ]
        ];

        $response = $client->put($uri, $options);
        $this->assertTrue($response->hasHeader("Location"));
        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey("nombre", $data);
        $this->assertTrue($data["nombre"] == "Actividad test 2");
    }
}
