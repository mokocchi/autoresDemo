<?php

namespace App\Tests\Controller;

use App\Entity\Actividad;
use App\Entity\Dominio;
use App\Entity\Planificacion;
use App\Entity\Salto;
use App\Entity\Tarea;
use App\Test\ApiTestCase;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ActividadesControllerTest extends ApiTestCase
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
        self::$resourceUri = self::$prefijo_api . "/actividades";
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

    /** @group post */
    public function testPost()
    {
        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
            'json' => [
                "nombre" => "Actividad test",
                "objetivo" => "Probar crear una actividad",
                "codigo" => self::$actividadCodigo,
                "dominio" => self::$dominioId,
                "idioma" => 1,
                "tipoPlanificacion" => 1,
                "estado" => 2
            ]
        ];

        $response = self::$client->post(self::$resourceUri, $options);
        $this->assertTrue($response->hasHeader("Location"));
        $data = $this->getJson($response);
        $this->assertEquals([
            "id",
            "nombre",
            "objetivo",
            "idioma",
            "dominio",
            "tipo_planificacion",
            "autor",
            "estado",
            "codigo",
            "_links"
        ], array_keys($data));
        $this->assertNotEmpty($data["id"]);
        $this->assertEquals("Actividad test", $data["nombre"]);
        $this->assertEquals("Probar crear una actividad", $data["objetivo"]);
        $this->assertEquals(self::$actividadCodigo, $data["codigo"]);
        $this->assertEquals(self::$dominioName, $data["dominio"]["nombre"]);
        $this->assertEquals("es", $data["idioma"]["code"]);
        $this->assertEquals("Secuencial", $data["tipo_planificacion"]["nombre"]);
        $this->assertEquals("Privado", $data["estado"]["nombre"]);
        $this->assertEquals("Pedro", $data["autor"]["nombre"]);
        $this->assertEquals(self::$resourceUri . '/' . $data["id"], $data['_links']['self']);
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
        $this->createDefaultActividad();
        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
            'json' => [
                "nombre" => "Actividad test",
                "objetivo" => "Probar crear una actividad",
                "codigo" => self::$actividadCodigo,
                "dominio" => self::$dominioId,
                "idioma" => 1,
                "tipoPlanificacion" => 1,
                "estado" => 2
            ]
        ];
        try {
            self::$client->post(self::$resourceUri, $options);
            $this->fail("No se detectó el código repetido");
        } catch (RequestException $e) {
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_BAD_REQUEST, "Ya existe una actividad con el mismo código");
        }
    }

    /** @group post */
    public function testPostRequiredParameters()
    {
        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
            'json' => [
                "objetivo" => "Probar crear una actividad",
                "dominio" => self::$dominioId,
                "codigo" => self::$actividadCodigo,
                "idioma" => 1,
                "tipoPlanificacion" => 1,
                "estado" => 1
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
    public function testPostWrongData()
    {
        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
            'json' => [
                "nombre" => "Actividad test",
                "objetivo" => "Probar crear una actividad",
                "codigo" => self::$actividadCodigo,
                "dominio" => self::$dominioId,
                "idioma" => 99,
                "tipoPlanificacion" => 1,
                "estado" => 2
            ]
        ];
        try {
            self::$client->post(self::$resourceUri, $options);
            $this->fail("No se detectó el id de idioma inválido");
        } catch (RequestException $e) {
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_BAD_REQUEST, "Se recibieron datos inválidos");
        }
    }

    /** @group patch */
    public function testPatch()
    {
        $id = $this->createDefaultActividad()->getId();
        $uri = self::$resourceUri . "/" . $id;
        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
            'json' => [
                "nombre" => "Actividad test 2"
            ]
        ];
        $response = self::$client->patch($uri, $options);
        $data = $this->getJson($response);
        $this->assertArrayHasKey("nombre", $data);
        $this->assertTrue($data["nombre"] == "Actividad test 2");
    }

    /** @group patch */
    public function testPatchUnauthorized()
    {
        $this->assertUnauthorized(Request::METHOD_PATCH, self::$resourceUri . "/" . 0);
    }

    /** @group patch */
    public function testPatchForbiddenRole()
    {
        $this->assertForbidden(Request::METHOD_PATCH, self::$resourceUri . "/" . 0, self::$usuarioAppToken);
    }

    /** @group patch */
    public function testPatchActividadNotOwned()
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
                "objetivo" => "Alterar una actividad ajena"
            ]
        ];
        try {
            self::$client->patch($uri, $options);
        } catch (RequestException $e) {
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_FORBIDDEN, "La actividad no pertenece al usuario actual");
        }
    }

    /** @group patch */
    public function testPatchWrongToken()
    {
        $this->assertWrongToken(Request::METHOD_PATCH, self::$resourceUri . "/" . 0);
    }

    /** @group patch */
    public function testPatchMissingJson()
    {
        $id = $this->createDefaultActividad()->getId();
        $this->assertNoJson(Request::METHOD_PATCH, self::$resourceUri . '/' . $id);
    }

    /** @group patch */
    public function testPatchModifyCodigo()
    {
        $id = $this->createDefaultActividad()->getId();
        $uri = self::$resourceUri . "/" . $id;
        $options = [
            "headers" => ["Authorization" => self::getAuthHeader()],
            "json" => [
                "codigo" => "codigonuevo"
            ]
        ];
        try {
            self::$client->patch($uri, $options);
            $this->fail("No se detectó el intento de modificar el código de la actividad");
        } catch (RequestException $e) {
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_BAD_REQUEST, "No se puede modificar el código de una actividad");
        }
    }

    /** @group patch */
    public function testPatchNotFound()
    {
        $uri = self::$resourceUri . '/' . 0;
        $this->assertNotFound(Request::METHOD_PATCH, $uri, "Actividad");
    }

    /** @group delete */
    public function testDelete()
    {
        $id = $this->createDefaultActividad()->getId();
        $uri = self::$resourceUri . "/" . $id;
        $response = self::$client->delete($uri, self::getDefaultOptions());
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    /** @group delete */
    public function testDeleteUnauthorized()
    {
        $this->assertUnauthorized(Request::METHOD_DELETE, self::$resourceUri . "/" . 0);
    }

    /** @group delete */
    public function testDeleteForbiddenRole()
    {
        $this->assertForbidden(Request::METHOD_DELETE, self::$resourceUri . "/" . 0, self::$usuarioAppToken);
    }

    /** @group delete */
    public function testDeleteWrongToken()
    {
        $this->assertWrongToken(Request::METHOD_DELETE, self::$resourceUri . "/" . 0);
    }

    /** @group delete */
    public function testDeleteNotFound()
    {
        $uri = self::$resourceUri . "/" . 0;
        $response = self::$client->delete($uri, self::getDefaultOptions());
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    /** @group getOne */
    public function testGetOne()
    {
        $id = $this->createDefaultActividad()->getId();
        $uri = self::$resourceUri . "/" . $id;
        $response = self::$client->get($uri, self::getDefaultOptions());
        $this->assertTrue($response->getStatusCode() == Response::HTTP_OK);
        $data = $this->getJson($response);
        $this->assertEquals([
            "id",
            "nombre",
            "objetivo",
            "idioma",
            "dominio",
            "tipo_planificacion",
            "autor",
            "estado",
            "codigo",
            "_links"
        ], array_keys($data));
        $this->assertNotEmpty($data["id"]);
        $this->assertEquals("Actividad test", $data["nombre"]);
        $this->assertEquals("Probar crear una actividad", $data["objetivo"]);
        $this->assertEquals(self::$actividadCodigo, $data["codigo"]);
        $this->assertEquals(self::$dominioName, $data["dominio"]["nombre"]);
        $this->assertEquals("es", $data["idioma"]["code"]);
        $this->assertEquals("Secuencial", $data["tipo_planificacion"]["nombre"]);
        $this->assertEquals("Privado", $data["estado"]["nombre"]);
        $this->assertEquals("Pedro", $data["autor"]["nombre"]);
        $this->assertEquals(self::$resourceUri . '/' . $id, $data['_links']['self']);
    }

    /** @group getOne */
    public function testGetUnauthorized()
    {
        $this->assertUnauthorized(Request::METHOD_GET, self::$resourceUri . "/" . 0);
    }

    /** @group getOne */
    public function testGetForbiddenRole()
    {
        $this->assertForbidden(Request::METHOD_GET, self::$resourceUri . "/" . 0, self::$usuarioAppToken);
    }

    /** @group getOne */
    public function testGetWrongToken()
    {
        $this->assertWrongToken(Request::METHOD_GET, self::$resourceUri . "/" . 0);
    }

    /** @group getOne */
    public function testGetNotOwned()
    {
        $id = $this->createActividad([
            "nombre" => "Actividad ajena",
            "codigo" => self::$actividadCodigo,
            "objetivo" => "Probar acceder a una actividad de otro autor",
            "autor" => self::$otherAutorEmail
        ])->getId();

        $uri = self::$resourceUri . "/" . $id;
        try {
            self::$client->get($uri, self::getDefaultOptions());
            $this->fail("No se detectó el intento de acceder a una actividad privada ajena");
        } catch (RequestException $e) {
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_FORBIDDEN, "La actividad es privada o no pertenece al usuario actual");
        }
    }

    /** @group getOne */
    public function testNotFoundGet()
    {
        $uri = self::$resourceUri . "/" . 0;
        $this->assertNotFound(Request::METHOD_GET, $uri, "Actividad");
    }

    /** @group getAllUser */
    public function testGetAllUserPaginated()
    {
        for ($i = 0; $i < 25; $i++) {
            $this->createActividad(array(
                "nombre" => "Actividad test",
                "codigo" => self::$actividadCodigo . $i,
                "objetivo" => "Probar la paginación de las actividades"
            ));
        }

        $this->createActividad(array(
            "nombre" => "Actividad not match",
            "codigo" => "codigo",
            "objetivo" => "Probar la paginación de las actividades"
        ));

        $this->createActividad(array(
            "nombre" => "Actividad test",
            "codigo" => "codigo",
            "objetivo" => "Probar la paginación de las actividades",
            "autor" => self::$otherAutorEmail
        ));
        $uri = self::$resourceUri . '/user?filter=test';

        $response = self::$client->get($uri, $this->getDefaultOptions());
        $this->assertEquals(200, $response->getStatusCode());
        $data = $this->getJson($response);
        $this->assertEquals(self::$actividadCodigo . 5, $data["results"][5]["codigo"]);
        $this->assertEquals(10, $data["count"]);
        $this->assertEquals(25, $data["total"]);
        $this->assertArrayHasKey("_links", $data);
        $this->assertArrayHasKey("next", $data["_links"]);
        $nextLink = $data["_links"]["next"];
        $response = self::$client->get($nextLink, $this->getDefaultOptions());

        $this->assertEquals(200, $response->getStatusCode());
        $data = $this->getJson($response);
        $this->assertEquals(self::$actividadCodigo . 15, $data["results"][5]["codigo"]);
        $this->assertEquals(10, $data["count"]);
        $this->assertEquals(10, $data["count"]);

        $this->assertArrayHasKey("_links", $data);
        $this->assertArrayHasKey("last", $data["_links"]);
        $lastLink = $data["_links"]["last"];
        $response = self::$client->get($lastLink, $this->getDefaultOptions());
        $data = $this->getJson($response);
        $this->assertEquals(5, $data["count"]);
        $this->assertEquals(5, count($data["results"]));

        $response = self::$client->get($uri, $this->getDefaultOptions());
    }

    /** @group getAllUser */
    public function testGetAllUnauthorized()
    {
        $this->assertUnauthorized(Request::METHOD_GET, self::$resourceUri . "/user");
    }

    /** @group getAllUser */
    public function testGetAllUserForbiddenRole()
    {
        $this->assertForbidden(Request::METHOD_GET, self::$resourceUri . "/user", self::$usuarioAppToken);
    }

    /** @group getAllUser */
    public function testGetAllUserWrongToken()
    {
        $this->assertWrongToken(Request::METHOD_GET, self::$resourceUri . "/user");
    }

    /** @group putTareas */
    public function testPutTareas()
    {
        $tareas = [];
        for ($i = 0; $i < 10; $i++) {
            $tarea = $this->createTarea([
                "nombre" => "Tarea test",
                "consigna" => "Probar la asociación de tareas",
                "codigo" => self::$tareaCodigo . $i,
                "tipo" => "simple"
            ]);
            $tareas[] = $tarea->getId();
        }
        $id = $this->createDefaultActividad()->getId();
        $uri = self::$resourceUri . '/' . $id . '/tareas';
        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
            'json' => [
                'tareas' => $tareas
            ]
        ];
        $response = self::$client->put($uri, $options);
        $this->assertTrue($response->getStatusCode() == Response::HTTP_OK);
        $data = $this->getJson($response);
        $this->assertEquals(["results"], array_keys($data));
        $this->assertEquals(10, count($data["results"]));
    }

    /** @group putTareas */
    public function testPutTareasUnauthorized()
    {
        $this->assertUnauthorized(Request::METHOD_PUT, self::$resourceUri . "/" . 0 . "/tareas");
    }

    /** @group putTareas */
    public function testPutTareasWrongToken()
    {
        $this->assertWrongToken(Request::METHOD_PUT, self::$resourceUri . "/" . 0 . "/tareas");
    }

    /** @group putTareas */
    public function testPutTareasForbiddenRole()
    {
        $this->assertForbidden(Request::METHOD_PUT, self::$resourceUri . "/" . 0 . "/tareas", self::$usuarioAppToken);
    }

    /** @group putTareas */
    public function testPutTareasMissingJson()
    {
        $id = $this->createDefaultActividad()->getId();
        $this->assertNoJson(Request::METHOD_PUT, self::$resourceUri . '/' . $id . "/tareas");
    }

    /** @group putTareas */
    public function testPutTareasActividadNotOwned()
    {
        $id = $this->createActividad([
            "nombre" => "Actividad ajena",
            "codigo" => self::$actividadCodigo,
            "objetivo" => "Probar acceder a una actividad de otro autor",
            "autor" => self::$otherAutorEmail
        ])->getId();

        $uri = self::$resourceUri . "/" . $id . "/tareas";
        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
            'json' => [
                "tareas" => []
            ]
        ];
        try {
            self::$client->put($uri, $options);
            $this->fail("No se detectó un intento de acceder a una actividad ajena");
        } catch (RequestException $e) {
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_FORBIDDEN, "La actividad no pertenece al usuario actual");
        }
    }

    /** @group putTareas */
    public function testPutTareasTareasNotOwned()
    {
        $id = $this->createDefaultActividad()->getId();
        $tareaId = $this->createTarea([
            "nombre" => "Tarea test",
            "consigna" => "Probar la asociación de tareas",
            "codigo" => self::$tareaCodigo,
            "tipo" => "simple",
            "autor" => self::$otherAutorEmail
        ])->getId();
        $uri = self::$resourceUri . "/" . $id . "/tareas";
        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
            'json' => [
                "tareas" => [$tareaId]
            ]
        ];
        try {
            self::$client->put($uri, $options);
            $this->fail("No se detectó un intento de acceder a una tarea ajena");
        } catch (RequestException $e) {
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_FORBIDDEN, "La tarea no pertenece al usuario actual");
        }
    }

    /** @group putTareas */
    public function testPutTareasTareasNotArray()
    {
        $id = $this->createDefaultActividad()->getId();
        $uri = self::$resourceUri . '/' . $id . '/tareas';
        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
            'json' => [
                'tareas' => "string"
            ]
        ];
        try {
            self::$client->put($uri, $options);
            $this->fail("No se detectó que el campo tareas no es array");
        } catch (RequestException $e) {
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_BAD_REQUEST, "El campo tareas tiene que ser un array");
        }
    }

    /** @group putTareas */
    public function testPutTareasTareasDeleted()
    {
        $actividad = $this->createDefaultActividad();
        $tarea = $this->createTarea([
            "nombre" => "Tarea test",
            "consigna" => "Probar la asociación de tareas",
            "codigo" => self::$tareaCodigo,
            "tipo" => "simple"
        ]);
        $actividad->addTarea($tarea);
        self::$em->persist($actividad);
        self::$em->flush();
        $id = $actividad->getId();

        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
            'json' => [
                'tareas' => []
            ]
        ];
        $uri = self::$resourceUri . "/" . $id . "/tareas";
        $response = self::$client->put($uri, $options);
        $this->assertTrue($response->getStatusCode() == Response::HTTP_OK);
        $data = $this->getJson($response);
        $this->assertEquals(["results"], array_keys($data));
        $this->assertEquals(0, count($data["results"]));
    }

    /** @group getAllTareas */
    public function testGetAllTareas()
    {
        $tareas = [];
        for ($i = 0; $i < 5; $i++) {
            $tareas[] = $this->createTarea([
                "nombre" => "Tarea test " . $i,
                "consigna" => "Probar el listado de tareas de una actividad",
                "codigo" => self::$tareaCodigo . $i,
                "tipo" => "simple"
            ]);
        }
        $actividad = $this->createDefaultActividad();
        foreach ($tareas as $tarea) {
            $actividad->addTarea($tarea);
            self::$em->persist($actividad);
            self::$em->flush();
        }

        $id = $actividad->getId();
        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
            'json' => [
                'tareas' => []
            ]
        ];
        $uri = self::$resourceUri . "/" . $id . "/tareas";
        $response = self::$client->get($uri, $options);
        $this->assertTrue($response->getStatusCode() == Response::HTTP_OK);
        $data = $this->getJson($response);
        $this->assertEquals(["results"], array_keys($data));
        $this->assertEquals(5, count($data["results"]));
    }

    /** @group getAllTareas */
    public function testGetAllTareasUnauthorized()
    {
        $this->assertUnauthorized(Request::METHOD_GET, self::$resourceUri . "/" . 0 . "/tareas");
    }

    /** @group getAllTareas */
    public function testGetAllTareasForbiddenRole()
    {
        $this->assertForbidden(Request::METHOD_GET, self::$resourceUri . "/" . 0 . "/tareas", self::$usuarioAppToken);
    }

    /** @group getAllTareas */
    public function testGetAllTareasWrongToken()
    {
        $this->assertWrongToken(Request::METHOD_GET, self::$resourceUri . "/" . 0 . "/tareas");
    }

    /** @group getAllTareas */
    public function testGetAllTareasNotOwned()
    {
        $id = $this->createActividad([
            "nombre" => "Actividad ajena",
            "codigo" => self::$actividadCodigo,
            "objetivo" => "Probar acceder a una actividad de otro autor",
            "autor" => self::$otherAutorEmail
        ])->getId();

        $uri = self::$resourceUri . "/" . $id . "/tareas";
        try {
            self::$client->get($uri, self::getDefaultOptions());
            $this->fail("No se detectó el intento de acceder a una actividad privada ajena");
        } catch (RequestException $e) {
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_FORBIDDEN, "La actividad es privada o no pertenece al usuario actual");
        }
    }

    /** @group getAllTareas */
    public function testNotFoundGetAllTareas()
    {
        $uri = self::$resourceUri . "/" . 0 . "/tareas";
        $this->assertNotFound(Request::METHOD_GET, $uri, "Actividad");
    }
}
