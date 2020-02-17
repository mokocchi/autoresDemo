<?php

declare(strict_types=1);

namespace App\Controller\Tests;

use App\Entity\AccessToken;
use App\Entity\Actividad;
use App\Entity\Dominio;
use App\Entity\Estado;
use App\Entity\Idioma;
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
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        /** @var ObjectManager $em */
        $em = self::getService("doctrine")->getManager();
        $actividad = $em->getRepository(Actividad::class)->findOneBy(["codigo" => self::$actividadCodigo]);;
        if ($actividad) {
            $em->remove($actividad);
            $em->flush();
        }
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        /** @var ObjectManager $em */
        $em = self::getService("doctrine")->getManager();
        $dominio = $em->getRepository(Dominio::class)->find(self::$dominioId);
        if ($dominio) {
            $em->remove($dominio);
            $em->flush();
        }
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
        $estado = $em->getRepository(Estado::class)->findOneBy(["nombre" => "Privado"]);
        $actividad->setEstado($estado);
        if (!array_key_exists("autor", $actividad_array)) {
            $accessToken = $em->getRepository(AccessToken::class)->findOneBy(["token" => self::$access_token]);
            $actividad->setAutor($accessToken->getUser());
        } else {
            $autor = $em->getRepository(Usuario::class)->findOneBy(["email" => "jp805313@gmail.com"]);
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
        $uri = self::$prefijo_api . "/actividades";

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

        $response = self::$client->post($uri, $options);
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
        $this->assertEquals("Ana", $data["autor"]["nombre"]);
    }


    public function testPostMissingFields()
    {
        $uri = self::$prefijo_api . "/actividades";

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
            self::$client->post($uri, $options);
        } catch (RequestException $e) {
            $this->assertEquals(Response::HTTP_BAD_REQUEST, $e->getResponse()->getStatusCode());
            $data = json_decode((string) $e->getResponse()->getBody(), true);
            $this->assertEquals([
                "status",
                "developer_message",
                "user_message",
                "error_code",
                "more_info"
            ],array_keys($data));
        }
    }

    public function testPatch()
    {
        $id = $this->createDefaultActividad();
        $uri = self::$prefijo_api . "/actividades/" . $id;
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

    public function testPatchMissingJson()
    {
        $id = $this->createDefaultActividad();
        $uri = self::$prefijo_api . "/actividades/" . $id; //TODO: id por codigos
        try {
            self::$client->patch($uri, self::getDefaultOptions());
        } catch (RequestException $e) {
            $this->assertEquals(Response::HTTP_BAD_REQUEST, $e->getResponse()->getStatusCode());
            $data = json_decode((string) $e->getResponse()->getBody(), true);
            $this->assertEquals([
                "status",
                "developer_message",
                "user_message",
                "error_code",
                "more_info"
            ],array_keys($data));
        }
    }

    public function testPatchModifyCodigo()
    {
        $id = $this->createDefaultActividad();
        $uri = self::$prefijo_api . "/actividades/" . $id;
        $options = [
            "headers" => ["Authorization" => self::getAuthHeader()],
            "json" => [
                "codigo" => "codigonuevo"
            ]
        ];
        try {
            self::$client->patch($uri, $options);
        } catch (RequestException $e) {
            $this->assertEquals(Response::HTTP_BAD_REQUEST, $e->getResponse()->getStatusCode());
            $data = json_decode((string) $e->getResponse()->getBody(), true);
            $this->assertEquals([
                "status",
                "developer_message",
                "user_message",
                "error_code",
                "more_info"
            ],array_keys($data));
        }
    }

    public function testDelete()
    {
        $id = $this->createDefaultActividad();
        $uri = self::$prefijo_api . "/actividades/" . $id;
        $response = self::$client->delete($uri, self::getDefaultOptions());
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testDeleteNotFound()
    {
        $uri = self::$prefijo_api . "/actividades/" . 0;
        $response = self::$client->delete($uri, self::getDefaultOptions());
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testGetOne()
    {
        $id = $this->createDefaultActividad();
        $uri = self::$prefijo_api . "/actividades/" . $id;
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
        $this->assertEquals("Ana", $data["autor"]["nombre"]);
    }

    public function testAccessDeniedGet()
    {
        $id = $this->createActividad([
            "nombre" => "Actividad ajena",
            "codigo" => self::$actividadCodigo,
            "objetivo" => "Probar acceder a una actividad de otro autor",
            "autor" => "jp805313@gmail.com"
        ]);

        $uri = self::$prefijo_api . "/actividades/" . $id;
        try {
            self::$client->get($uri, self::getDefaultOptions());
        } catch (RequestException $e) {
            $this->assertEquals(Response::HTTP_FORBIDDEN, $e->getResponse()->getStatusCode());
            $data = json_decode((string) $e->getResponse()->getBody(), true);
            $this->assertEquals([
                "status",
                "developer_message",
                "user_message",
                "error_code",
                "more_info"
            ],array_keys($data));
        }
    }

    public function testNotFoundGet()
    {
        $uri = self::$prefijo_api . "/actividades/" . 0;
        try {
            self::$client->get($uri, self::getDefaultOptions());
        } catch (RequestException $e) {
            $this->assertEquals(Response::HTTP_NOT_FOUND, $e->getResponse()->getStatusCode());
            $data = json_decode((string) $e->getResponse()->getBody(), true);
            $this->assertEquals([
                "status",
                "developer_message",
                "user_message",
                "error_code",
                "more_info"
            ],array_keys($data));
        }
    }
}
