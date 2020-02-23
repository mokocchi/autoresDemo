<?php

namespace App\Test\Controller\v1;

use App\Entity\Actividad;
use App\Entity\Dominio;
use App\Entity\Planificacion;
use App\Entity\Salto;
use App\Entity\Tarea;
use App\Test\ApiTestCase;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PlanificacionesControllerTest extends ApiTestCase
{
    private static $autorEmail = "autor@test.com";
    private static $otherAutorEmail = "autor2@test.com";
    private static $usuarioAppEmail = "usuario@test.com";
    private static $usuarioAppToken;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $dominio = new Dominio();
        $dominio->setNombre(self::$dominioName);
        self::$em->persist($dominio);
        self::$em->flush();
        self::$dominioId = $dominio->getId();
        self::$resourceUri = self::$prefijo_api . "/planificaciones";
        $usuario = self::createAutor(self::$autorEmail);
        self::$access_token = self::getNewAccessToken($usuario);
        self::createAutor(self::$otherAutorEmail);
        $usuarioApp = self::createUsuarioApp(self::$usuarioAppEmail);
        self::$usuarioAppToken = self::getNewAccessToken($usuarioApp);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        self::truncateEntities([Actividad::class, Tarea::class, Salto::class, Planificacion::class]);
        self::truncateTable("actividad_tarea");
        self::truncateTable("salto_tarea");
        self::truncateTable("tarea_inicial");
        self::truncateTable("tarea_opcional");
        self::$em->clear();
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        self::truncateEntities([Dominio::class]);
        self::removeUsuarios();
    }

    /** @param array $saltoArray Array of origen, condicion, destinos and maybe respuesta */
    private function createSalto(array $saltoArray)
    {
        $salto = new Salto();
        $salto->setOrigen($saltoArray["origen"]);
        $salto->setCondicion($saltoArray["condicion"]);
        foreach ($saltoArray["destinos"] as $destino) {
            $salto->addDestino($destino);
        }
        $salto->setPlanificacion($saltoArray["planificacion"]);
        $salto->setRespuesta(array_key_exists("respuesta", $saltoArray) ? $saltoArray["respuesta"] : null);
        self::$em->persist($salto);
        self::$em->flush();
        return $salto;
    }

    /** @group put */
    public function testPut()
    {
        $actividad = $this->createActividad(
            [
                "nombre" => "Actividad test",
                "objetivo" => "Probar el seteo de planificaciones",
                "codigo" => self::$actividadCodigo
            ]
        );
        $tareas = [];
        for ($i = 1; $i <= 10; $i++) {
            $tareas[] = $this->createTarea([
                "nombre" => "Tarea test " . $i,
                "consigna" => "Probar el seteo de planificaciones",
                "codigo" => self::$tareaCodigo . $i,
                "tipo" => "simple"
            ]);
        }
        $ids = [];
        foreach ($tareas as $tarea) {
            $actividad->addTarea($tarea);
            $ids[] = $tarea->getId();
        }
        self::$em->persist($actividad);
        self::$em->flush();
        $id = $actividad->getId();
        $options = [
            "headers" => ["Authorization" => self::getAuthHeader()],
            "json" => [
                "saltos" => [
                    [
                        "origen" => $ids[0],
                        "condicion" => "ALL",
                        "destinos" => [$ids[1]],
                    ],
                    [
                        "origen" => $ids[1],
                        "condicion" => "ALL",
                        "destinos" => [$ids[2]],
                    ],
                    [
                        "origen" => $ids[2],
                        "condicion" => "ALL",
                        "destinos" => [
                            $ids[3],
                            $ids[5],
                            $ids[8]
                        ]
                    ],
                    [
                        "origen" => $ids[3],
                        "condicion" => "ALL",
                        "destinos" => [$ids[4]],
                    ],
                    [
                        "origen" => $ids[5],
                        "condicion" => "ALL",
                        "destinos" => [$ids[6]],
                    ],
                    [
                        "origen" => $ids[6],
                        "condicion" => "ALL",
                        "destinos" => [$ids[7]],
                    ],
                    [
                        "origen" => $ids[8],
                        "condicion" => "ALL",
                        "destinos" => [$ids[9]],
                    ],
                ],
                "opcionales" => [
                    $ids[4],
                    $ids[6],
                    $ids[9]
                ],
                "iniciales" => [
                    $ids[2],
                    $ids[5]
                ]
            ]
        ];
        $uri = self::$resourceUri . '/' . $id;
        $response = self::$client->put($uri, $options);
        $data = $this->getJson($response);
        $this->assertEquals(["iniciales_ids", "opcionales_ids", "saltos"], array_keys($data));
        $this->assertEquals(7, count($data["saltos"]));
        $this->assertEquals(3, count($data["opcionales_ids"]));
        $this->assertEquals(2, count($data["iniciales_ids"]));
    }

    /** @group put */
    public function testPutUnauthorized()
    {
        $this->assertUnauthorized(Request::METHOD_PUT, self::$resourceUri . "/" . 0);
    }

    /** @group put */
    public function testPutWrongToken()
    {
        $this->assertWrongToken(Request::METHOD_PUT, self::$resourceUri . "/" . 0);
    }

    /** @group put */
    public function testPutForbiddenRole()
    {
        $this->assertForbidden(Request::METHOD_PUT, self::$resourceUri . "/" . 0, self::$usuarioAppToken);
    }

    /** @group put */
    public function testPutMissingJson()
    {
        $id = $this->createDefaultActividad()->getId();
        $this->assertNoJson(Request::METHOD_PUT, self::$resourceUri . '/' . $id);
    }

    /** @group put */
    public function testPutActividadNotOwned()
    {
        $id = $this->createActividad([
            "nombre" => "Actividad ajena",
            "codigo" => self::$actividadCodigo,
            "objetivo" => "Probar acceder a una actividad de otro autor",
            "autor" => self::$otherAutorEmail
        ])->getId();

        $uri = self::$resourceUri . "/" . $id;
        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
            'json' => [
                "saltos" => [],
                "iniciales" => [],
                "opcionales" => []
            ]
        ];
        try {
            self::$client->put($uri, $options);
            $this->fail("No se detectó un intento de acceder a una actividad ajena");
        } catch (RequestException $e) {
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_FORBIDDEN, "La actividad no pertenece al usuario actual");
        }
    }

    /** @group put */
    public function testPutTareasNotOwned()
    {
        $id = $this->createDefaultActividad()->getId();
        $tareaId = $this->createTarea([
            "nombre" => "Tarea test",
            "consigna" => "Probar la asociación de tareas",
            "codigo" => self::$tareaCodigo,
            "tipo" => "simple",
            "autor" => self::$otherAutorEmail
        ])->getId();
        $uri = self::$resourceUri . "/" . $id;
        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
            'json' => [
                "saltos" => [],
                "iniciales" => [$tareaId],
                "opcionales" => []
            ]
        ];
        try {
            self::$client->put($uri, $options);
            $this->fail("No se detectó un intento de acceder a una tarea ajena");
        } catch (RequestException $e) {
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_FORBIDDEN, "La tarea es privada o no pertenece al usuario actual");
        }
    }

    /** @group put */
    public function testPutSaltosNotArray()
    {
        $id = $this->createDefaultActividad()->getId();
        $uri = self::$resourceUri . '/' . $id;
        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
            'json' => [
                'saltos' => "string",
                'iniciales' => [],
                'opcionales' => []
            ]
        ];
        try {
            self::$client->put($uri, $options);
            $this->fail("No se detectó que el campo saltos no es array");
        } catch (RequestException $e) {
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_BAD_REQUEST, "El campo saltos tiene que ser un array", Response::HTTP_BAD_REQUEST);
        }
    }

    /** @group put */
    public function testPutTareasNotAttached()
    {
        $actividad = $this->createActividad(
            [
                "nombre" => "Actividad test",
                "objetivo" => "Probar el seteo de planificaciones",
                "codigo" => self::$actividadCodigo
            ]
        );
        $tareaId = $this->createTarea([
            "nombre" => "Tarea test",
            "consigna" => "Probar el seteo de planificaciones",
            "codigo" => self::$tareaCodigo,
            "tipo" => "simple"
        ])->getId();
        self::$em->persist($actividad);
        self::$em->flush();
        $id = $actividad->getId();
        $options = [
            "headers" => ["Authorization" => self::getAuthHeader()],
            "json" => [
                "saltos" => [],
                "opcionales" => [$tareaId],
                "iniciales" => []
            ]
        ];
        $uri = self::$resourceUri . '/' . $id;

        try {
            self::$client->put($uri, $options);
            $this->fail("No se detectó una tarea que no pertenece a la actividad");
        } catch (RequestException $e) {
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_BAD_REQUEST, sprintf("La tarea %s no pertenece a la actividad %s", self::$tareaCodigo, self::$actividadCodigo));
        }
    }

    /** @group put */
    public function testPutSaltosNoFinals()
    {
        $actividad = $this->createActividad(
            [
                "nombre" => "Actividad test",
                "objetivo" => "Probar el seteo de planificaciones",
                "codigo" => self::$actividadCodigo
            ]
        );
        $tareas = [];
        for ($i = 1; $i <= 3; $i++) {
            $tareas[] = $this->createTarea([
                "nombre" => "Tarea test " . $i,
                "consigna" => "Probar el seteo de planificaciones",
                "codigo" => self::$tareaCodigo . $i,
                "tipo" => "simple"
            ]);
        }
        $ids = [];
        foreach ($tareas as $tarea) {
            $actividad->addTarea($tarea);
            $ids[] = $tarea->getId();
        }
        self::$em->persist($actividad);
        self::$em->flush();
        $id = $actividad->getId();
        $options = [
            "headers" => ["Authorization" => self::getAuthHeader()],
            "json" => [
                "saltos" => [
                    [
                        "origen" => $ids[0],
                        "condicion" => "ALL",
                        "destinos" => [$ids[1]],
                    ],
                    [
                        "origen" => $ids[1],
                        "condicion" => "ALL",
                        "destinos" => [$ids[2]],
                    ],
                    [
                        "origen" => $ids[2],
                        "condicion" => "ALL",
                        "destinos" => [$ids[0]]
                    ],
                ],
                "opcionales" => [],
                "iniciales" => []
            ]
        ];
        $uri = self::$resourceUri . '/' . $id;
        try {
            self::$client->put($uri, $options);
            $this->fail("No se detectó que el grafo no tiene salida");
        } catch (RequestException $e) {
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_BAD_REQUEST, "El grafo no tiene salida");
        }
    }

    /** @group get */
    public function testGet()
    {
        $actividad = $this->createActividad(
            [
                "nombre" => "Actividad test",
                "objetivo" => "Probar el seteo de planificaciones",
                "codigo" => self::$actividadCodigo
            ]
        );
        $tareas = [];
        for ($i = 1; $i <= 10; $i++) {
            $tareas[] = $this->createTarea([
                "nombre" => "Tarea test " . $i,
                "consigna" => "Probar el seteo de planificaciones",
                "codigo" => self::$tareaCodigo . $i,
                "tipo" => "simple"
            ]);
        }
        $ids = [];
        foreach ($tareas as $tarea) {
            $actividad->addTarea($tarea);
            $ids[] = $tarea->getId();
        }
        $planificacion = $actividad->getPlanificacion();
        $salto1 = $this->createSalto([
            "origen" => $tareas[0],
            "condicion" => "ALL",
            "destinos" => [$tareas[1], $tareas[2]],
            "planificacion" => $planificacion
        ]);
        $salto2 = $this->createSalto([
            "origen" => $tareas[3],
            "condicion" => "ALL",
            "destinos" => [$tareas[4]],
            "planificacion" => $planificacion
        ]);
        $planificacion->addInicial($tareas[2]);
        $planificacion->addOpcional($tareas[7]);
        self::$em->persist($salto1);
        self::$em->persist($salto2);
        self::$em->persist($planificacion);
        self::$em->flush();
        $id = $actividad->getId();
        $response = self::$client->get(self::$resourceUri . "/" . $id, self::getDefaultOptions());
        $data = $this->getJson($response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals([
            "iniciales_ids",
            "opcionales_ids",
            "saltos"
        ], array_keys($data));
        $this->assertTrue(is_array($data["saltos"]));
        $this->assertTrue(is_array($data["iniciales_ids"]));
        $this->assertTrue(is_array($data["opcionales_ids"]));
        $this->assertEquals(2, count($data["saltos"]));
        $this->assertEquals(1, count($data["iniciales_ids"]));
        $this->assertEquals(1, count($data["opcionales_ids"]));
    }

     /** @group get */
     public function testGetUnauthorized()
     {
         $this->assertUnauthorized(Request::METHOD_GET, self::$resourceUri . "/0");
     }
 
     /** @group get */
     public function testGetUserForbiddenRole()
     {
         $this->assertForbidden(Request::METHOD_GET, self::$resourceUri . "/0", self::$usuarioAppToken);
     }
 
     /** @group get */
     public function testGetAllUserWrongToken()
     {
         $this->assertWrongToken(Request::METHOD_GET, self::$resourceUri . "/0");
     }
 
}
