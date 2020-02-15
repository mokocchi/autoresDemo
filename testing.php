<?php

require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;

try {
    $client = new Client(
        [
            'base_uri' => 'http://localhost:8080/'
        ]
    );
    $options = [
        'headers' => ['X-AUTH-CREDENTIALS' => true],
        'form_params' => [
            'client_id' => '',
            'client_secret' => ''
        ]
    ];
    $response = $client->post('/oauth/v2/token', $options);

    $data = json_decode((string) $response->getBody());
    $access_token = $data->access_token;

    $options = [
        'headers' => ['Authorization' => 'Bearer ' . $access_token]
    ];
    $response = $client->get("/api/v1.0/me", $options);
    echo (string) $response->getBody();
    echo "\n\n";
} catch (Exception $e) {
    echo $e->getMessage();
}
