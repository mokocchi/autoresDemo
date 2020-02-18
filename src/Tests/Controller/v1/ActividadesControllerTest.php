<?php

declare(strict_types=1);

namespace App\Controller\Tests;

use App\Entity\AccessToken;
use App\Entity\Actividad;
use App\Entity\Dominio;
use App\Entity\Estado;
use App\Entity\Idioma;
use App\Entity\Planificacion;
use App\Entity\TipoPlanificacion;
use App\Entity\Usuario;
use App\Test\ApiTestCase;
use Doctrine\Persistence\ObjectManager;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\Response;

class ActividadesControllerTest extends ApiTestCase
{
    private static $dominioName = "Test";
    private static $actividadCodigo = "actividadtest";
    private static $dominioId;
    private static $resourceUri;
    private static $autorEmail = "autor@test.com";
    private static $otherAutorEmail = "autor2@test.com";
    private static $usuarioAppEmail = "usuario@test.com";
    private static $usuarioAppToken;
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        /** @var ObjectManager $em */
        $em = self::getService("doctrine")->getManager();
        $dominio = new Dominio();
        $dominio->setNombre(self::$dominioName);
        $em->persist($dominio);
        $em->flush();
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
        /** @var ObjectManager $em */
        $em = self::getService("doctrine")->getManager();
        $actividades = $em->getRepository(Actividad::class)->findBy(["codigo" => self::$actividadCodigo]);;
        foreach ($actividades as $actividad) {
            $planificacion = $actividad->getPlanificacion();
            $em->remove($actividad);
            $em->flush();
            $em->remove($planificacion);
            $em->flush();
        }
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        /** @var ObjectManager $em */
        $em = self::getService("doctrine")->getManager();
        $dominio = $em->getRepository(Dominio::class)->find(self::$dominioId);
        if ($dominio) {
            $em->remove($dominio);
            $em->flush();
        }
        $em->clear();
        self::removeUsuario(self::$autorEmail);
        self::removeUsuario(self::$otherAutorEmail);
        self::removeUsuario(self::$usuarioAppEmail);
    }

    private function createActividad(array $actividad_array): int
    {
        /** @var ObjectManager $em */
        $em = self::getService("doctrine")->getManager();
        $actividad = new Actividad();
        $actividad->setNombre($actividad_array["nombre"]);
        $actividad->setObjetivo($actividad_array["objetivo"]);
        $actividad->setCodigo($actividad_array["codigo"]);
        $dominio = $em->getRepository(Dominio::class)->find(self::$dominioId);
        $actividad->setDominio($dominio);
        $idioma = $em->getRepository(Idioma::class)->findOneBy(["code" => "es"]);
        $actividad->setIdioma($idioma);
        $tipoPlanificacion = $em->getRepository(TipoPlanificacion::class)->findOneBy(["nombre" => "Secuencial"]);
        $actividad->setTipoPlanificacion($tipoPlanificacion);
        $actividad->setPlanificacion(new Planificacion());
        $estado = $em->getRepository(Estado::class)->findOneBy(["nombre" => "Privado"]);
        $actividad->setEstado($estado);
        if (!array_key_exists("autor", $actividad_array)) {
            $accessToken = $em->getRepository(AccessToken::class)->findOneBy(["token" => self::$access_token]);
            $actividad->setAutor($accessToken->getUser());
        } else {
            $autor = $em->getRepository(Usuario::class)->findOneBy(["email" => $actividad_array["autor"]]);
            $actividad->setAutor($autor);
        }
        $em->persist($actividad);
        $em->flush();
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

    public function testPostUnauthorized() {
        $this->assertUnauthorized(self::POST, self::$resourceUri);

    }

    public function testPostForbiddenRole() {
        $this->assertForbidden(self::POST, self::$resourceUri, self::$usuarioAppToken);
    }

    public function testPostWrongToken() {
        $this->assertWrongToken(self::POST, self::$resourceUri);
    }

    public function testPostCodigoAlreadyUsed() {
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
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_BAD_REQUEST);
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
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_BAD_REQUEST);
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
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_BAD_REQUEST);
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
            try{ 
                $response = self::$client->patch($uri, $options);
            } catch (RequestException $e) {
                $this->dumpError($e);
            }
        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey("nombre", $data);
        $this->assertTrue($data["nombre"] == "Actividad test 2");
    }
    
    public function testPatchUnauthorized() {
        $this->assertUnauthorized(self::PATCH, self::$resourceUri . "/" . 0);
        
    }
    
    public function testPatchForbiddenRole() {
        $this->assertForbidden(self::PATCH, self::$resourceUri . "/" . 0, self::$usuarioAppToken);
    }
    
    public function testPatchWrongToken() {
        $this->assertWrongToken(self::PATCH, self::$resourceUri . "/" . 0);
    }
    
    public function testPatchMissingJson()
    {
        $id = $this->createDefaultActividad();
        $uri = self::$prefijo_api . "/actividades/" . $id; //TODO: id por codigos
        try {
            self::$client->patch($uri, self::getDefaultOptions());
            $this->fail("No se detectó que no se envió json");
        } catch (RequestException $e) {
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_BAD_REQUEST);
        }
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
                $this->assertErrorResponse($e->getResponse(), Response::HTTP_BAD_REQUEST);
            }
        }
        
        public function testPatchNotFound()
        {
            $uri = self::$resourceUri . '/' . 0;
            $options = [
                "headers" => ["Authorization" => self::getAuthHeader()],
            "json" => [
                ]
        ];
        try {
            self::$client->patch($uri, $options);
            $this->fail("No se detectó que la actividad no existe");
        } catch (RequestException $e) {
            
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_NOT_FOUND);
        }
    }
    
    public function testDelete()
    {
        $id = $this->createDefaultActividad();
        $uri = self::$resourceUri . "/" . $id;
        $response = self::$client->delete($uri, self::getDefaultOptions());
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }
    
    public function testDeleteUnauthorized() {
        $this->assertUnauthorized(self::DELETE, self::$resourceUri . "/" . 0);
        
    }
    
    public function testDeleteForbiddenRole() {
        $this->assertForbidden(self::DELETE, self::$resourceUri . "/" . 0, self::$usuarioAppToken);
    }

    public function testDeleteWrongToken() {
        $this->assertWrongToken(self::DELETE, self::$resourceUri . "/" . 0);
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

    public function testAccessDeniedGet()
    {
        $id = $this->createActividad([
            "nombre" => "Actividad ajena",
            "codigo" => self::$actividadCodigo,
            "objetivo" => "Probar acceder a una actividad de otro autor",
            "autor" => "autor2@test.com"
        ]);

        $uri = self::$resourceUri . "/" . $id;
        try {
            self::$client->get($uri, self::getDefaultOptions());
        } catch (RequestException $e) {
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_FORBIDDEN);
        }
    }

    public function testNotFoundGet()
    {
        $uri = self::$resourceUri . "/" . 0;
        try {
            self::$client->get($uri, self::getDefaultOptions());
        } catch (RequestException $e) {
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_NOT_FOUND);
        }
    }
}
