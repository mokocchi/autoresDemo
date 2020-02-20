<?php

namespace App\Test\Controller\v1;

use App\Entity\AccessToken;
use App\Entity\Dominio;
use App\Entity\Estado;
use App\Entity\Tarea;
use App\Entity\TipoTarea;
use App\Entity\Usuario;
use App\Test\ApiTestCase;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function PHPSTORM_META\map;

class TareasControllerTest extends ApiTestCase
{

    private static $dominioName = "Test";
    private static $dominioId;
    private static $resourceUri;
    private static $autorEmail = "autor1@gmail.com";
    private static $otherAutorEmail = "autor2@gmail.com";
    private static $usuarioAppEmail = "usuario@gmail.com";
    private static $usuarioAppToken;
    private static $tareaCodigo = "tareaTest";

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
        /** @var ObjectManager $em */
        $em = self::getService("doctrine")->getManager();
        $tareas = $em->getRepository(Tarea::class)->findBy(["codigo" => self::$tareaCodigo]);;
        foreach ($tareas as $tarea) {
            $em->remove($tarea);
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

    private function createTarea(array $tareaArray)
    {
        $tarea = new Tarea();
        $tarea->setNombre($tareaArray["nombre"]);
        $tarea->setConsigna($tareaArray["consigna"]);
        $tarea->setCodigo($tareaArray["codigo"]);
        $em = self::getService("doctrine")->getManager();
        $dominio = $em->getRepository(Dominio::class)->find(self::$dominioId);
        $tarea->setDominio($dominio);
        $tipoTarea = $em->getRepository(TipoTarea::class)->findOneBy(["codigo" => $tareaArray["tipo"]]);
        $tarea->setTipo($tipoTarea);
        if (!array_key_exists("autor", $tareaArray)) {
            $accessToken = $em->getRepository(AccessToken::class)->findOneBy(["token" => self::$access_token]);
            $tarea->setAutor($accessToken->getUser());
        } else {
            $autor = $em->getRepository(Usuario::class)->findOneBy(["email" => $tareaArray["autor"]]);
            $tarea->setAutor($autor);
        }
        $estado = $em->getRepository(Estado::class)->findOneBy(["nombre" => "Privado"]);
        $tarea->setEstado($estado);
        $em->persist($tarea);
        $em->flush();
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

    public function testpostTarea()
    {
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
        $response = self::$client->post(self::$resourceUri, $options);
        $this->assertTrue($response->hasHeader("Location"));
        $data = json_decode((string) $response->getBody(), true);
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
        $id = $this->createDefaultTarea();
        $uri = self::$resourceUri . "/" . $id;
        $response = self::$client->get($uri, self::getDefaultOptions());
        $this->assertTrue($response->getStatusCode() == Response::HTTP_OK);
        $data = json_decode((string) $response->getBody(), true);
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
        ]);

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
}
