<?php

namespace App\Test;

use App\Entity\AccessToken;
use App\Entity\Actividad;
use App\Entity\Client as EntityClient;
use App\Entity\Dominio;
use App\Entity\Estado;
use App\Entity\Idioma;
use App\Entity\Planificacion;
use App\Entity\Role;
use App\Entity\Tarea;
use App\Entity\TipoPlanificacion;
use App\Entity\TipoTarea;
use App\Entity\Usuario;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use OAuth2\OAuth2;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTestCase extends KernelTestCase
{
    protected static $client;
    protected static $access_token;
    protected static $prefijo_api = '/api/v1.0';
    protected static $resourceUri;
    protected static $actividadCodigo = "actividadtest";
    protected static $tareaCodigo = "tareatest";
    protected static $dominioName = "Test";
    protected static $dominioId;

    protected static $apiProblemArray = [
        "status",
        "developer_message",
        "user_message",
        "error_code",
        "more_info"
    ];
    protected static $em;

    protected static function getAuthHeader()
    {
        return 'Bearer ' . self::$access_token;
    }

    protected static function getDefaultOptions()
    {
        return ["headers" => ['Authorization' => self::getAuthHeader()]];
    }

    private function assertApiProblemResponse($response, $message)
    {
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals(self::$apiProblemArray, array_keys($data));
        $this->assertEquals($message, $data["developer_message"]);
    }

    protected function assertErrorResponse($response, $statusCode, $message)
    {
        $this->assertEquals($statusCode, $response->getStatusCode());
        $this->assertApiProblemResponse($response, $message);
    }

    protected function dumpError(RequestException $e)
    {
        $data = json_decode((string) $e->getResponse()->getBody(), true);
        dd($data["user_message"]);
    }

    protected static function createAutor(string $email)
    {
        return self::createUsuario([
            "email" => $email,
            "nombre" => "Pedro",
            "apellido" => "Sánchez",
            "googleid" => "2000",
            "role" => "ROLE_AUTOR"
        ]);
    }

    protected static function createUsuarioApp($email)
    {
        return self::createUsuario([
            "email" => "$email",
            "nombre" => "María",
            "apellido" => "Del Carril",
            "googleid" => "3000",
            "role" => "ROLE_USUARIO_APP"
        ]);
    }

    protected static function createUsuario(array $usuarioArray)
    {
        $user = new Usuario();
        $user->setEmail($usuarioArray["email"]);
        $user->setNombre($usuarioArray["nombre"]);
        $user->setApellido($usuarioArray["apellido"]);
        $user->setGoogleid($usuarioArray["googleid"]);
        $role = self::$em->getRepository(Role::class)->findOneBy(["name" => $usuarioArray["role"]]);
        $user->addRole($role);

        $client = new EntityClient();
        $client->setAllowedGrantTypes(array(OAuth2::GRANT_TYPE_CLIENT_CREDENTIALS));
        $user->setOAuthClient($client);
        self::$em->persist($client);
        self::$em->flush();
        self::$em->persist($user);
        self::$em->flush();
        return $user;
    }

    /**
     * @param array $actividad_array Array of nombre, objetivo, codigo and maybe autor, maybe estado
     */
    protected function createActividad(array $actividad_array): Actividad
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
        $planificacion = new Planificacion();
        self::$em->persist($planificacion);
        $actividad->setPlanificacion($planificacion);
        if (array_key_exists("estado", $actividad_array)) {
            $estado = self::$em->getRepository(Estado::class)->findOneBy(["nombre" => $actividad_array["estado"]]);
        } else {
            $estado = self::$em->getRepository(Estado::class)->findOneBy(["nombre" => "Privado"]);
        }
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
        return $actividad;
    }

    protected function createDefaultActividad(): Actividad
    {
        return $this->createActividad([
            "nombre" => "Actividad test",
            "objetivo" => "Probar crear una actividad",
            "codigo" => self::$actividadCodigo,
        ]);
    }

    /**
     * @param array $tareaArray Array of nombre, consigna, codigo, tipo and maybe autor, maybe estado
     */
    protected function createTarea(array $tareaArray): Tarea
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
        if (array_key_exists("estado", $tareaArray)) {
            $estado = self::$em->getRepository(Estado::class)->findOneBy(["nombre" => $tareaArray["estado"]]);
        } else {
            $estado = self::$em->getRepository(Estado::class)->findOneBy(["nombre" => "Privado"]);
        }
        $tarea->setEstado($estado);
        self::$em->persist($tarea);
        self::$em->flush();
        return $tarea;
    }

    protected function createDefaultTarea(): Tarea
    {
        return $this->createTarea([
            "nombre" => "Tarea test",
            "consigna" => "Probar las tareas",
            "codigo" => self::$tareaCodigo,
            "tipo" => "simple"
        ]);
    }

    protected function createDominio(?string $nombre = null): int
    {
        $dominio = new Dominio();
        $dominio->setNombre(is_null($nombre) ? self::$dominioName : $nombre);
        self::$em->persist($dominio);
        self::$em->flush();
        return $dominio->getId();
    }

    /** @return AccessToken  */
    protected static function getNewAccessToken(Usuario $usuario)
    {
        $client = $usuario->getOauthClient();
        $client_id = $client->getPublicId();
        $secret = $client->getSecret();

        $options = [
            'headers' => ['X-AUTH-CREDENTIALS' => true],
            'form_params' => [
                'client_id' => $client_id,
                'client_secret' => $secret
            ]
        ];
        $response = self::$client->post('/api/oauth/v2/token', $options);

        $data = json_decode((string) $response->getBody());
        return $data->access_token;
    }

    public static function setUpBeforeClass(): void
    {
        self::bootKernel();
        self::$client = new Client(
            [
                'base_uri' => 'http://localhost:80/'
            ]
        );
        self::$em = self::getService("doctrine")->getManager();
    }

    protected static function removeUsuarios()
    {
        self::truncateTable("access_token");
        self::truncateTable("usuario_role");
        self::truncateEntities([Usuario::class, EntityClient::class]);
    }

    protected static function removeUsuario($email)
    {
        $usuario = self::$em->getRepository(Usuario::class)->findOneBy(["email" => $email]);
        if (!is_null($usuario)) {
            $access_tokens = self::$em->getRepository(AccessToken::class)->findBy(["user" => $usuario->getId()]);
            foreach ($access_tokens as $token) {
                self::$em->remove($token);
            }
            $roles = self::$em->getRepository(Role::class)->findAll();
            foreach ($roles as $role) {
                $usuario->removeRole($role);
            }
            self::$em->flush();
            self::$em->remove($usuario);
            self::$em->flush();
            $client = $usuario->getOauthClient();
            self::$em->remove($client);
            self::$em->flush();
        }
    }

    protected function tearDown(): void
    {
    }

    protected static function getService($id)
    {
        return self::$kernel->getContainer()->get($id);
    }

    protected static function truncateTable($name)
    {
        $connection = self::$em->getConnection();
        $platform = $connection->getDatabasePlatform();
        $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 0;');
        $truncateSql = $platform->getTruncateTableSQL($name);
        $connection->executeUpdate($truncateSql);
        $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 1;');
    }

    protected static function truncateEntities(array $entities)
    {
        $connection = self::$em->getConnection();
        $databasePlatform = $connection->getDatabasePlatform();
        if ($databasePlatform->supportsForeignKeyConstraints()) {
            $connection->query('SET FOREIGN_KEY_CHECKS=0');
        }
        foreach ($entities as $entity) {
            $query = $databasePlatform->getTruncateTableSQL(
                self::$em->getClassMetadata($entity)->getTableName()
            );
            $connection->executeUpdate($query);
        }
        if ($databasePlatform->supportsForeignKeyConstraints()) {
            $connection->query('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    protected function assertUnauthorized($method, $uri)
    {
        try {
            switch ($method) {
                case Request::METHOD_GET:
                    self::$client->get($uri);
                    break;
                case Request::METHOD_POST:
                    self::$client->post($uri);
                    break;
                case Request::METHOD_PATCH:
                    self::$client->patch($uri);
                    break;
                case Request::METHOD_PUT:
                    self::$client->put($uri);
                    break;
                case Request::METHOD_DELETE:
                    self::$client->delete($uri);
                default:
                    break;
            }
            $this->fail("No se detectó una petición no autorizada");
        } catch (RequestException $e) {
            self::assertErrorResponse($e->getResponse(), Response::HTTP_UNAUTHORIZED, 'Se requiere autenticación OAuth');
        }
    }

    protected function assertForbidden($method, $uri, $access_token)
    {
        $options = [
            "headers" => ["Authorization" => "Bearer " . $access_token]
        ];
        try {
            switch ($method) {
                case Request::METHOD_GET:
                    self::$client->get($uri, $options);
                    break;
                case Request::METHOD_POST:
                    self::$client->post($uri, $options);
                    break;
                case Request::METHOD_PUT:
                    self::$client->put($uri, $options);
                    break;
                case Request::METHOD_PATCH:
                    self::$client->patch($uri, $options);
                    break;
                case Request::METHOD_DELETE:
                    self::$client->delete($uri, $options);
                default:
                    break;
            }
            $this->fail("No se detectó una petición sin permisos suficientes");
        } catch (RequestException $e) {
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_FORBIDDEN, "No tenés los permisos suficientes para acceder al recurso");
        }
    }

    protected function assertWrongToken($method, $uri)
    {
        $options = [
            "headers" => ["Authorization" => "Bearer %token%"]
        ];
        try {
            switch ($method) {
                case Request::METHOD_GET:
                    self::$client->get($uri, $options);
                    break;
                case Request::METHOD_POST:
                    self::$client->post($uri, $options);
                    break;
                case Request::METHOD_PUT:
                    self::$client->put($uri, $options);
                    break;
                case Request::METHOD_PATCH:
                    self::$client->patch($uri, $options);
                    break;
                case Request::METHOD_DELETE:
                    self::$client->delete($uri, $options);
                default:
                    break;
            }
            $this->fail("No se detectó una petición con un token erróneo");
        } catch (RequestException $e) {
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_UNAUTHORIZED, "El token expiró o es inválido");
        }
    }

    protected function assertNoJson($method, $uri)
    {
        $options = [
            'headers' => ['Authorization' => self::getAuthHeader()]
        ];
        try {
            switch ($method) {
                case Request::METHOD_POST:
                    self::$client->post($uri, $options);
                    break;
                case Request::METHOD_PUT:
                    self::$client->put($uri, $options);
                    break;
                case Request::METHOD_PATCH:
                    self::$client->patch($uri, $options);
                    break;
                default:
                    break;
            }
            $this->fail("No se detectó que no hay json en el request");
        } catch (RequestException $e) {
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_BAD_REQUEST, "No hay campos en el json");
        }
    }

    public function assertNotFound($method, $uri, $className)
    {
        try {
            switch ($method) {
                case Request::METHOD_GET:
                    self::$client->get($uri, self::getDefaultOptions());
                case Request::METHOD_PATCH:
                    $options = [
                        "headers" => ["Authorization" => self::getAuthHeader()],
                        "json" => []
                    ];
                    self::$client->patch($uri, $options);
                    break;
                case Request::METHOD_PUT:
                    $options = [
                        "headers" => ["Authorization" => self::getAuthHeader()],
                        "json" => []
                    ];
                    self::$client->put($uri, $options);
                    break;
                case Request::METHOD_DELETE:
                    self::$client->delete($uri, self::getDefaultOptions());
                    break;
                default:
                    break;
            }
        } catch (RequestException $e) {
            $this->assertErrorResponse($e->getResponse(), Response::HTTP_NOT_FOUND, sprintf("No se encontró: %s 0", $className));
        }
    }

    protected function getJson($response)
    {
        return json_decode((string) $response->getBody(), true);
    }
}
