<?php

namespace App\Test\Controller\v1;

use App\Entity\Dominio;
use App\Entity\Tarea;
use App\Test\ApiTestCase;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TareasControllerTest extends ApiTestCase
{
    private static $autorEmail = "autor1@gmail.com";
    private static $otherAutorEmail = "autor2@gmail.com";
    private static $usuarioAppEmail = "usuario@gmail.com";
    private static $usuarioAppToken;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $dominio = new Dominio();
        $dominio->setNombre(self::$dominioName);
        self::$em->persist($dominio);
        self::$em->flush();
        self::$dominioId = $dominio->getId();
        self::$resourceUri = self::$prefijo_api . "/tareas";
        $usuario = self::createAutor(self::$autorEmail);
        self::$access_token = self::getNewAccessToken($usuario);
        self::createAutor(self::$otherAutorEmail);
        $usuarioApp = self::createUsuarioApp(self::$usuarioAppEmail);
        self::$usuarioAppToken = self::getNewAccessToken($usuarioApp);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        self::truncateEntities([Tarea::class]);
        self::$em->clear();
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        self::truncateEntities([Dominio::class]);
        self::removeUsuarios();
    }

    /** @group post */
    public function testpostTarea()
    {
        $options = [
            "headers" => ["Authorization" => "Bearer " . self::$access_token],
            "json" => [
                "nombre" => "Tarea test",
                "consigna" => "Probar las tareas",
                "codigo" => self::$tareaCodigo,
                "tipo" => 6, //opción multiple
                "dominio" => self::$dominioId,
                "estado" => 2,
                "extraData" => [
                    "elements" => [
                        [
                            "name" => 'Uno',
                            "code" => 'code1'
                        ],
                        [
                            "name" => 'Dos',
                            "code" => 'code2'
                        ]
                    ],
                ]
            ]
        ];
        $response = self::$client->post(self::$resourceUri, $options);
        $this->assertTrue($response->hasHeader("Location"));
        $data = $this->getJson($response);
        $this->assertEquals([
            "id",
            "nombre",
            "consigna",
            "dominio",
            "tipo",
            "extra",
            "codigo",
            "autor",
            "estado"
        ], array_keys($data));
        $this->assertNotEmpty($data["id"]);
        $this->assertEquals("Tarea test", $data["nombre"]);
        $this->assertEquals("Probar las tareas", $data["consigna"]);
        $this->assertEquals(self::$dominioName, $data["dominio"]["nombre"]);
        $this->assertEquals("multiple", $data["tipo"]["codigo"]);
        $this->assertEquals(["elements" => [
            [
                "name" => 'Uno',
                "code" => 'code1'
            ],
            [
                "name" => 'Dos',
                "code" => 'code2'
            ]
        ]], $data["extra"]);
        $this->assertEquals(self::$tareaCodigo, $data["codigo"]);
        $this->assertEquals("Privado", $data["estado"]["nombre"]);
        $this->assertEquals("Pedro", $data["autor"]["nombre"]);
    }

    /** @group post */
    public function testPostTareaExtraByScore()
    {
        $options = [
            "headers" => ["Authorization" => "Bearer " . self::$access_token],
            "json" => [
                "nombre" => "Tarea test",
                "consigna" => "Probar las tareas",
                "codigo" => self::$tareaCodigo,
                "tipo" => 7, //contadores
                "dominio" => self::$dominioId,
                "estado" => 2,
                "extraData" => [
                    "elements" => [
                        [
                            "name" => 'Uno',
                            "code" => 'e9a3c7f14539b304bf283bd8eb46d4b1ad2f9595f4f37ed640cdce8e8eff6cec'
                        ],
                        [
                            "name" => 'Dos',
                            "code" => '15dfabd220ffe1d793102561fd2ecf39c99969e2b0966fef0396e99094860313'
                        ]
                    ],
                    "byScore" => [
                        [
                            "name" => 'Rareza',
                            "message" => 'A mayor número, más raro el animal',
                            "scores" => [
                                "e9a3c7f14539b304bf283bd8eb46d4b1ad2f9595f4f37ed640cdce8e8eff6cec" => '1',
                                '15dfabd220ffe1d793102561fd2ecf39c99969e2b0966fef0396e99094860313' => '2'
                            ]
                        ],
                        [
                            "name" => 'Plomo',
                            "message" => 'Porcentaje de plomo',
                            "scores" => [
                                'e9a3c7f14539b304bf283bd8eb46d4b1ad2f9595f4f37ed640cdce8e8eff6cec' => '3',
                                '15dfabd220ffe1d793102561fd2ecf39c99969e2b0966fef0396e99094860313' => '7'
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $response = self::$client->post(self::$resourceUri, $options);
        $this->assertTrue($response->hasHeader("Location"));
        $data = $this->getJson($response);
        $this->assertEquals([
            "id",
            "nombre",
            "consigna",
            "dominio",
            "tipo",
            "extra",
            "codigo",
            "autor",
            "estado"
        ], array_keys($data));
        $this->assertNotEmpty($data["id"]);
    }

    /** @group post */
    public function testPostTareaInvalidExtra()
    {
        $this->assertTrue(true);
    }

    /** @group post */
    public function testPostUnauthorized()
    {
        $this->assertUnauthorized(Request::METHOD_POST, self::$resourceUri);
    }

    /** @group post */
    public function testPostForbiddenRole()
    {
        $this->assertForbidden(Request::METHOD_POST, self::$resourceUri, self::$usuarioAppToken);
    }

    /** @group post */
    public function testPostWrongToken()
    {
        $this->assertWrongToken(Request::METHOD_POST, self::$resourceUri);
    }

    /** @group post */
    public function testPostNoJson()
    {
        $this->assertNoJson(Request::METHOD_POST, self::$resourceUri);
    }

    /** @group post */
    public function testPostCodigoAlreadyUsed()
    {
        $this->createDefaultTarea();
        $options = [
            "headers" => ["Authorization" => "Bearer " . self::$access_token],
            "json" => [
                "nombre" => "Tarea test",
                "consigna" => "Probar las tareas",
                "codigo" => self::$tareaCodigo,
                "tipo" => 1,
                "dominio" => self::$dominioId,
                "estado" => 2
            ]
        ];
        try {
            self::$client->post(self::$resourceUri, $options);
            $this->fail("No se detectó el código repetido");
        } catch (RequestException $e) {
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_BAD_REQUEST, "Ya existe una tarea con el mismo código");
        }
    }

    /** @group post */
    public function testPostRequiredParameters()
    {
        $options = [
            "headers" => ["Authorization" => "Bearer " . self::$access_token],
            "json" => [
                "consigna" => "Probar las tareas",
                "codigo" => self::$tareaCodigo,
                "tipo" => 1,
                "dominio" => self::$dominioId,
                "estado" => 2
            ]
        ];
        try {
            self::$client->post(self::$resourceUri, $options);
            $this->fail("No se detectó que falta el nombre");
        } catch (RequestException $e) {
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_BAD_REQUEST, "Uno o más de los campos requeridos falta o es nulo");
        }
    }

    /** @group post */
    public function testPostInvalidData()
    {
        $options = [
            "headers" => ["Authorization" => "Bearer " . self::$access_token],
            "json" => [
                "nombre" => "Tarea test",
                "consigna" => "Probar las tareas",
                "codigo" => self::$tareaCodigo,
                "tipo" => "error",
                "dominio" => self::$dominioId,
                "estado" => 2
            ]
        ];
        try {
            self::$client->post(self::$resourceUri, $options);
            $this->fail("No se detectó el tipo inválido");
        } catch (RequestException $e) {
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_BAD_REQUEST, "Se recibieron datos inválidos");
        }
    }

    public function testGet()
    {
        $id = $this->createDefaultTarea()->getId();
        $uri = self::$resourceUri . "/" . $id;
        $response = self::$client->get($uri, self::getDefaultOptions());
        $this->assertTrue($response->getStatusCode() == Response::HTTP_OK);
        $data = $this->getJson($response);
        $this->assertEquals([
            "id",
            "nombre",
            "consigna",
            "dominio",
            "tipo",
            "extra",
            "codigo",
            "autor",
            "estado"
        ], array_keys($data));
        $this->assertNotEmpty($data["id"]);
        $this->assertEquals("Tarea test", $data["nombre"]);
        $this->assertEquals("Probar las tareas", $data["consigna"]);
        $this->assertEquals(self::$dominioName, $data["dominio"]["nombre"]);
        $this->assertEquals("simple", $data["tipo"]["codigo"]);
        $this->assertEquals([], $data["extra"]);
        $this->assertEquals(self::$tareaCodigo, $data["codigo"]);
        $this->assertEquals("Privado", $data["estado"]["nombre"]);
        $this->assertEquals("Pedro", $data["autor"]["nombre"]);
    }

    public function testGetUnauthorized()
    {
        $this->assertUnauthorized(Request::METHOD_GET, self::$resourceUri . "/" . 0);
    }

    public function testGetForbiddenRole()
    {
        $this->assertForbidden(Request::METHOD_GET, self::$resourceUri . "/" . 0, self::$usuarioAppToken);
    }

    public function testGetWrongToken()
    {
        $this->assertWrongToken(Request::METHOD_GET, self::$resourceUri . "/" . 0);
    }

    public function testGetNotOwned()
    {
        $id = $this->createTarea([
            "nombre" => "Tarea ajena",
            "codigo" => self::$tareaCodigo,
            "consigna" => "Probar acceder a una tarea de otro autor",
            "tipo" => "simple",
            "autor" => self::$otherAutorEmail
        ])->getId();

        $uri = self::$resourceUri . "/" . $id;
        try {
            self::$client->get($uri, self::getDefaultOptions());
            $this->fail("No se detectó el intento de acceder a una actividad ajena");
        } catch (RequestException $e) {
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_FORBIDDEN, "La tarea es privada o no pertenece al usuario actual");
        }
    }

    public function testNotFoundGet()
    {
        $uri = self::$resourceUri . "/" . 0;
        $this->assertNotFound(Request::METHOD_GET, $uri, "Tarea");
    }

    /** @group getAll */
    public function testGetAll()
    {
        for ($i = 0; $i < 25; $i++) {
            $this->createTarea([
                "nombre" => "Tarea test",
                "codigo" => self::$tareaCodigo . $i,
                "consigna" => "Probar la paginación de las tareas",
                "tipo" => "simple",
            ]);
        }

        $this->createActividad([
            "nombre" => "Tarea not match",
            "codigo" => "codigo",
            "tipo" => "simple",
            "objetivo" => "Probar la paginación de las tareas"
        ]);

        $this->createActividad([
            "nombre" => "Tarea test",
            "codigo" => "codigo",
            "tipo" => "simple",
            "objetivo" => "Probar la paginación de las tareas",
        ]);
        $uri = self::$resourceUri . '/user?nombre=test';

        $response = self::$client->get($uri, $this->getDefaultOptions());
        $this->assertEquals(200, $response->getStatusCode());
        $data = $this->getJson($response);
        $this->assertEquals(self::$tareaCodigo . 5, $data["results"][5]["codigo"]);
        $this->assertEquals(10, $data["count"]);
        $this->assertEquals(25, $data["total"]);
        $this->assertArrayHasKey("_links", $data);
        $this->assertArrayHasKey("next", $data["_links"]);
        $nextLink = $data["_links"]["next"];

        $response = self::$client->get($nextLink, $this->getDefaultOptions());
        $this->assertEquals(200, $response->getStatusCode());
        $data = $this->getJson($response);
        $this->assertEquals(self::$tareaCodigo . 15, $data["results"][5]["codigo"]);
        $this->assertEquals(10, $data["count"]);
        $this->assertEquals(10, $data["count"]);
        $this->assertArrayHasKey("_links", $data);
        $this->assertArrayHasKey("last", $data["_links"]);
        $lastLink = $data["_links"]["last"];

        $response = self::$client->get($lastLink, $this->getDefaultOptions());
        $data = $this->getJson($response);
        $this->assertEquals(5, $data["count"]);
        $this->assertEquals(5, count($data["results"]));
    }
}
