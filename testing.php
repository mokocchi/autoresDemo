<?php

require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

try {
    $client = new Client(
        [
            'base_uri' => 'http://localhost:8080/'
        ]
    );

    echo "Start authentication";
    echo "\n\n";

    $options = [
        'headers' => ['X-AUTH-CREDENTIALS' => true],
        'form_params' => [
            'client_id' => '',
            'client_secret' => ''
        ]
    ];
    $response = $client->post('/api/oauth/v2/token', $options);

    $data = json_decode((string) $response->getBody());
    $access_token = $data->access_token;
    if (!is_null($access_token)) {
        echo "Authentication OK";
        echo "\n\n";
    }
    $auth_header = 'Bearer ' . $access_token;

    $prefijo_api = '/api/v1.0';

    $default_options = [
        'headers' => ['Authorization' => $auth_header]
    ];
    echo "GET " . $prefijo_api . "/me";
    echo "\n\n";

    $response = $client->get($prefijo_api . "/me", $default_options);
    echo (string) $response->getBody();
    echo "\n\n";

    $options = [
        'headers' => ['Authorization' => $auth_header],
        'json' => [
            "nombre" => "Test",
        ]
    ];
    echo "POST " . $prefijo_api . "/dominios";
    echo "\n\n";
    $response = $client->post($prefijo_api . "/dominios", $options);

    $location = $response->getHeader("Location")[0];
    echo "Location: " . $location;
    echo "\n\n";

    $exploded_location = explode("/", $location);
    $dominio_id = $exploded_location[count($exploded_location) - 1];

    $options = [
        'headers' => ['Authorization' => $auth_header],
        'json' => [
            "nombre" => "Actividad test",
            "objetivo" => "Probar crear una actividad",
            "dominio" => $dominio_id,
            "idioma" => 1,
            "tipoPlanificacion" => 1,
            "estado" => 1
        ]
    ];

    echo "POST " . $prefijo_api . "/actividades";
    echo "\n\n";
    $response = $client->post($prefijo_api . "/actividades", $options);

    $location = $response->getHeader("Location")[0];
    echo "Location: " . $location;
    echo "\n\n";

    $exploded_location = explode("/", $location);
    $actividad_id = $exploded_location[count($exploded_location) - 1];

    echo "GET " . $prefijo_api . "/actividades/" . $actividad_id;
    echo "\n\n";
    $response = $client->get($prefijo_api . "/actividades/" . $actividad_id, $default_options);

    echo $response->getBody();
    echo "\n\n";

    try {
        echo "GET " . $prefijo_api . "/actividades/" . "0";
        echo "\n\n";
        $response = $client->get($prefijo_api . "/actividades/0", $default_options);
    } catch (RequestException $e) {
        echo "Error " . $e->getResponse()->getStatusCode();
        echo "\n\n";
    }
} catch (RequestException $e) {
    echo $e->getMessage();
    //echo $e->getResponse()->getBody()->getContents();
} catch (Exception $e) {
    echo $e->getMessage();
}
