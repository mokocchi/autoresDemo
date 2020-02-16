<?php

declare(strict_types=1);

namespace App\Controller\Tests;

use GuzzleHttp\Client;

class DominioControllerTest extends \PHPUnit\Framework\TestCase
{
    private $access_token;
    public function setUp() {
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
    public function testpost()
    {
        $client = new Client(
            [
                'base_uri' => 'http://localhost:8080/'
            ]
        );
        $auth_header = 'Bearer ' . $this->access_token;

        $prefijo_api = '/api/v1.0';

        $uri = $prefijo_api . "/dominios";

        $options = [
            'headers' => ['Authorization' => $auth_header],
            'json' => [
                "nombre" => "Test",
            ]
        ];

        $response = $client->post($uri, $options);
        $this->assertTrue($response->hasHeader("Location"));
        $data = json_decode((string) $response->getBody(),true);
        $this->assertArrayHasKey("nombre", $data);
    }
}
