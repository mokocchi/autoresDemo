<?php

declare(strict_types=1);

namespace App\Controller\Tests;

use GuzzleHttp\Client;

class DominioControllerTest extends \PHPUnit\Framework\TestCase
{
    private $access_token;
    private $client;
    private $prefijo_api = '/api/v1.0';
    private function getAuthHeader()
    {
        return 'Bearer ' . $this->access_token;
    }
    public function setUp() {
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
    }

    public function tearDown() {
        $uri = $this->prefijo_api . "/public/dominios?nombre=Test";
        $options = [
            'headers' => ['Authorization' => $this->getAuthHeader()]
        ];
        $response = $this->client->get($uri, $options);
        $data = json_decode((string) $response->getBody(),true);

        $dominios = $data["results"];

        foreach ($dominios as $dominio) {
            $uri = $this->prefijo_api . "/dominios/" . $dominio["id"];
            $response = $this->client->delete($uri, $options);
            print (string) $response->getBody();
        }
    }

    public function testpost()
    {
        $uri = $this->prefijo_api . "/dominios";

        $options = [
            'headers' => ['Authorization' => $this->getAuthHeader()],
            'json' => [
                "nombre" => "Test",
            ]
        ];

        $response = $this->client->post($uri, $options);
        $this->assertTrue($response->hasHeader("Location"));
        $data = json_decode((string) $response->getBody(),true);
        $this->assertArrayHasKey("nombre", $data);
    }
}
