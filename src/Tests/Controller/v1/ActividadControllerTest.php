<?php

declare(strict_types=1);

namespace App\Controller\Tests;

use App\Entity\AccessToken;
use App\Entity\Actividad;
use App\Entity\Dominio;
use App\Entity\Estado;
use App\Entity\Idioma;
use App\Entity\TipoPlanificacion;
use App\Test\ApiTestCase;
use Doctrine\Persistence\ObjectManager;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\Response;

class ActividadControllerTest extends ApiTestCase
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

    public function testPost()
    {
        $uri = self::$prefijo_api . "/actividades";

        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
            'json' => [
                "nombre" => "Actividad test",
                "objetivo" => "Probar crear una actividad",
                "codigo" => "actividadtest",
                "dominio" => self::$dominioId,
                "idioma" => 1,
                "tipoPlanificacion" => 1,
                "estado" => 1
            ]
        ];

        $response = self::$client->post($uri, $options);
        $this->assertTrue($response->hasHeader("Location"));
        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey("nombre", $data);
    }


    public function testPostMissingFields()
    {
        $uri = self::$prefijo_api . "/actividades";

        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
            'json' => [
                "objetivo" => "Probar crear una actividad",
                "dominio" => self::$dominioId,
                "codigo" => "actividadtest",
                "idioma" => 1,
                "tipoPlanificacion" => 1,
                "estado" => 1
            ]
        ];

        try {
            self::$client->post($uri, $options);
        } catch (RequestException $e) {
            $this->assertTrue($e->getResponse()->getStatusCode() == Response::HTTP_BAD_REQUEST);
        }
    }

    private function createUsuario(): int
    {
        /** @var ObjectManager $em */
        $em = self::getService("doctrine")->getManager();
        $actividad = new Actividad();
        $actividad->setNombre("Actividad test");
        $actividad->setObjetivo("Probar crear una actividad");
        $actividad->setCodigo(self::$actividadCodigo);
        $dominio = $em->getRepository(Dominio::class)->find(self::$dominioId);
        $actividad->setDominio($dominio);
        $idioma = $em->getRepository(Idioma::class)->find(1);
        $actividad->setIdioma($idioma);
        $tipoPlanificacion = $em->getRepository(TipoPlanificacion::class)->find(1);
        $actividad->setTipoPlanificacion($tipoPlanificacion);
        $estado = $em->getRepository(Estado::class)->find(1);
        $actividad->setEstado($estado);
        $accessToken = $em->getRepository(AccessToken::class)->findOneBy(["token" => self::$access_token]);
        $actividad->setAutor($accessToken->getUser());
        $em->persist($actividad);
        $em->flush();
        return $actividad->getId();
    }
    public function testPut()
    {
        $id = $this->createUsuario();
        $uri = self::$prefijo_api . "/actividades/" . $id;
        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()],
            'json' => [
                "nombre" => "Actividad test 2"
            ]
        ];
        $response = self::$client->put($uri, $options);
        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey("nombre", $data);
        $this->assertTrue($data["nombre"] == "Actividad test 2");
    }

    public function PutMissingJson()
    {
        $id = $this->createUsuario();
        $uri = self::$prefijo_api . "/actividades/" . $id; //TODO: id por codigos
        try {
            self::$client->put($uri, self::getDefaultOptions());
        } catch (RequestException $e) {
            $this->assertTrue($e->getResponse()->getStatusCode() == Response::HTTP_BAD_REQUEST);
        }
    }

    public function testDelete()
    {
        $id = $this->createUsuario();
        $uri = self::$prefijo_api . "/actividades/" . $id;
        $response = self::$client->delete($uri, self::getDefaultOptions());
        $this->assertTrue($response->getStatusCode() == Response::HTTP_NO_CONTENT);
    }

    public function testGetOne()
    {
        $id = $this->createUsuario();
        $uri = self::$prefijo_api . "/actividades/" . $id;
        $response = self::$client->get($uri, self::getDefaultOptions());
        $this->assertTrue($response->getStatusCode() == Response::HTTP_OK);
        $data = json_decode((string) $response->getBody(), true);
        //todo=sort keys
        $this->assertEquals([
            "nombre",
            "objetivo",
            "dominio",
            "codigo",
            "idioma",
            "tipoPlanificacion",
            "estado",
            "autor",
            "id"
        ], array_keys($data));
    }
}
