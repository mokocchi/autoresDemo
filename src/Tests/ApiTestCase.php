<?php

namespace App\Test;

use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ApiTestCase extends KernelTestCase
{

    protected static $client;
    protected static $access_token;
    protected static $prefijo_api = '/api/v1.0';
    protected static function getAuthHeader()
    {
        return 'Bearer ' . self::$access_token;
    }

    protected static function getDefaultOptions() {
        return ["headers" => ['Authorization' => self::getAuthHeader()]];
    }

    public static function setUpBeforeClass(): void
    {
        self::bootKernel();
        self::$client = new Client(
            [
                'base_uri' => 'http://localhost:80/'
            ]
        );
        $options = [
            'headers' => ['X-AUTH-CREDENTIALS' => true],
            'form_params' => [
            ]
        ];
        $response = self::$client->post('/api/oauth/v2/token', $options);

        $data = json_decode((string) $response->getBody());
        self::$access_token = $data->access_token;
    }

    protected function tearDown(): void
    {
    }

    protected static function getService($id)
    {
        return self::$kernel->getContainer()->get($id);
    }
}
