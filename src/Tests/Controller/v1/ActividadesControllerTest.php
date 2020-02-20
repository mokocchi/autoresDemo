<?php

declare(strict_types=1);

namespace App\Controller\Tests;

use App\Entity\AccessToken;
use App\Entity\Actividad;
use App\Entity\Dominio;
use App\Entity\Estado;
use App\Entity\Idioma;
use App\Entity\Planificacion;
use App\Entity\Tarea;
use App\Entity\TipoPlanificacion;
use App\Entity\TipoTarea;
use App\Entity\Usuario;
use App\Test\ApiTestCase;
use Doctrine\Persistence\ObjectManager;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ActividadesControllerTest extends ApiTestCase
{
    private static $dominioName = "Test";
    private static $actividadCodigo = "actividadtest";
    private static $tareaCodigo = "tareatest";
    private static $tareaCodigo2 = "tareatest2";
    private static $dominioId;
    private static $resourceUri;
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
        self::truncateEntities([Actividad::class, Tarea::class]);
        self::truncateTable("actividad_tarea");
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        self::truncateEntities([Dominio::class]);
        self::removeUsuarios();
    }

    private function createActividad(array $actividad_array): int
    {
        $actividad = new Actividad();
        $actividad->setNombre($actividad_array["nombre"]);
        $actividad->setObjetivo($actividad_array["objetivo"]);
        $actividad->setCodigo($actividad_array["codigo"]);
        $dominio = self::$em->getRepository(Dominio::class)->find(self::$dominioId);
        $actividad->setDominio($dominio);
        $idioma = self::$em->getRepository(Idioma::class)->findOneBy(["code" => "es"]);
        $actividad->setIdioma($idioma);
        $tipoPlanificacion = self::$em->getRepository(TipoPlanificacion::class)->findOneBy(["nombre" => "Secuencial"]);
        $actividad->setTipoPlanificacion($tipoPlanificacion);
        $actividad->setPlanificacion(new Planificacion());
        $estado = self::$em->getRepository(Estado::class)->findOneBy(["nombre" => "Privado"]);
        $actividad->setEstado($estado);
        if (!array_key_exists("autor", $actividad_array)) {
            $accessToken = self::$em->getRepository(AccessToken::class)->findOneBy(["token" => self::$access_token]);
            $actividad->setAutor($accessToken->getUser());
        } else {
            $autor = self::$em->getRepository(Usuario::class)->findOneBy(["email" => $actividad_array["autor"]]);
            $actividad->setAutor($autor);
        }
        self::$em->persist($actividad);
        self::$em->flush();
        return $actividad->getId();
    }

    private function createDefaultActividad(): int
    {
        return $this->createActividad([
            "nombre" => "Actividad test",
            "objetivo" => "Probar crear una actividad",
            "codigo" => self::$actividadCodigo,
        ]);
    }

    private function createTarea(array $tareaArray)
    {
        $tarea = new Tarea();
        $tarea->setNombre($tareaArray["nombre"]);
        $tarea->setConsigna($tareaArray["consigna"]);
        $tarea->setCodigo($tareaArray["codigo"]);
        $dominio = self::$em->getRepository(Dominio::class)->find(self::$dominioId);
        $tarea->setDominio($dominio);
        $tipoTarea = self::$em->getRepository(TipoTarea::class)->findOneBy(["codigo" => $tareaArray["tipo"]]);
        $tarea->setTipo($tipoTarea);
        if (!array_key_exists("autor", $tareaArray)) {
            $accessToken = self::$em->getRepository(AccessToken::class)->findOneBy(["token" => self::$access_token]);
            $tarea->setAutor($accessToken->getUser());
        } else {
            $autor = self::$em->getRepository(Usuario::class)->findOneBy(["email" => $tareaArray["autor"]]);
            $tarea->setAutor($autor);
        }
        $estado = self::$em->getRepository(Estado::class)->findOneBy(["nombre" => "Privado"]);
        $tarea->setEstado($estado);
        self::$em->persist($tarea);
        self::$em->flush();
        return $tarea->getId();
    }

    private function createDefaultTarea()
    {
        return $this->createTarea([
            "nombre" => "Tarea test",
            "consigna" => "Probar las tareas",
            "codigo" => self::$tareaCodigo,
            "tipo" => "simple"
        ]);
    }

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
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals([
            "id",
            "nombre",
            "objetivo",
            "idioma",
            "dominio",
            "tipo_planificacion",
            "planificacion",
            "autor",
            "estado",
            "codigo"
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
    }

    public function testPostUnauthorized()
    {
        $this->assertUnauthorized(Request::METHOD_POST, self::$resourceUri);
    }

    public function testPostForbiddenRole()
    {
        $this->assertForbidden(Request::METHOD_POST, self::$resourceUri, self::$usuarioAppToken);
    }

    public function testPostWrongToken()
    {
        $this->assertWrongToken(Request::METHOD_POST, self::$resourceUri);
    }

    public function testPostNoJson()
    {
        $this->assertNoJson(Request::METHOD_POST, self::$resourceUri);
    }

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

    public function testPatch()
    {
        $id = $this->createDefaultActividad();
        $uri = self::$resourceUri . "/" . $id;
        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
            'json' => [
                "nombre" => "Actividad test 2"
            ]
        ];
        $response = self::$client->patch($uri, $options);
        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey("nombre", $data);
        $this->assertTrue($data["nombre"] == "Actividad test 2");
    }

    public function testPatchUnauthorized()
    {
        $this->assertUnauthorized(Request::METHOD_PATCH, self::$resourceUri . "/" . 0);
    }

    public function testPatchForbiddenRole()
    {
        $this->assertForbidden(Request::METHOD_PATCH, self::$resourceUri . "/" . 0, self::$usuarioAppToken);
    }

    public function testPatchActividadNotOwned()
    {
        $id = $this->createActividad([
            "nombre" => "Actividad ajena",
            "codigo" => self::$actividadCodigo,
            "objetivo" => "Probar acceder a una actividad de otro autor",
            "autor" => self::$otherAutorEmail
        ]);

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

    public function testPatchWrongToken()
    {
        $this->assertWrongToken(Request::METHOD_PATCH, self::$resourceUri . "/" . 0);
    }

    public function testPatchMissingJson()
    {
        $id = $this->createDefaultActividad();
        $this->assertNoJson(Request::METHOD_PATCH, self::$resourceUri . '/' . $id);
    }

    public function testPatchModifyCodigo()
    {
        $id = $this->createDefaultActividad();
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

    public function testPatchNotFound()
    {
        $uri = self::$resourceUri . '/' . 0;
        $this->assertNotFound(Request::METHOD_PATCH, $uri, "Actividad");
    }

    public function testDelete()
    {
        $id = $this->createDefaultActividad();
        $uri = self::$resourceUri . "/" . $id;
        $response = self::$client->delete($uri, self::getDefaultOptions());
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testDeleteUnauthorized()
    {
        $this->assertUnauthorized(Request::METHOD_DELETE, self::$resourceUri . "/" . 0);
    }

    public function testDeleteForbiddenRole()
    {
        $this->assertForbidden(Request::METHOD_DELETE, self::$resourceUri . "/" . 0, self::$usuarioAppToken);
    }

    public function testDeleteWrongToken()
    {
        $this->assertWrongToken(Request::METHOD_DELETE, self::$resourceUri . "/" . 0);
    }

    public function testDeleteNotFound()
    {
        $uri = self::$resourceUri . "/" . 0;
        $response = self::$client->delete($uri, self::getDefaultOptions());
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testGetOne()
    {
        $id = $this->createDefaultActividad();
        $uri = self::$resourceUri . "/" . $id;
        $response = self::$client->get($uri, self::getDefaultOptions());
        $this->assertTrue($response->getStatusCode() == Response::HTTP_OK);
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals([
            "id",
            "nombre",
            "objetivo",
            "idioma",
            "dominio",
            "tipo_planificacion",
            "planificacion",
            "autor",
            "estado",
            "codigo"
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
        $id = $this->createActividad([
            "nombre" => "Actividad ajena",
            "codigo" => self::$actividadCodigo,
            "objetivo" => "Probar acceder a una actividad de otro autor",
            "autor" => self::$otherAutorEmail
        ]);

        $uri = self::$resourceUri . "/" . $id;
        try {
            self::$client->get($uri, self::getDefaultOptions());
            $this->fail("No se detectó el intento de acceder a una actividad ajena");
        } catch (RequestException $e) {
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_FORBIDDEN, "La actividad es privada o no pertenece al usuario actual");
        }
    }

    public function testNotFoundGet()
    {
        $uri = self::$resourceUri . "/" . 0;
        $this->assertNotFound(Request::METHOD_GET, $uri, "Actividad");
    }

    public function testaddTareasToActividad()
    {
        $tareaId = $this->createDefaultTarea();
        $tarea2Id = $this->createTarea([
            "nombre" => "Tarea test 2",
            "consigna" => "Probar la asociación de tareas",
            "codigo" => self::$tareaCodigo2,
            "tipo" => "simple"
        ]);
        $id = $this->createDefaultActividad();
        $uri = self::$resourceUri . '/' . $id . '/tareas';
        $options = [
            'headers' => ['Authorization' => 'Bearer ' . self::$access_token],
            'json' => [
                'tarea' => [$tareaId, $tarea2Id]
            ]
        ];
        $response = self::$client->post($uri, $options);
        $this->assertTrue($response->getStatusCode() == Response::HTTP_OK);
    }
}
