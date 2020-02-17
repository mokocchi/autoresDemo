<?php

namespace App\Test;

use App\Entity\AccessToken;
use App\Entity\Client as EntityClient;
use App\Entity\Role;
use App\Entity\Usuario;
use GuzzleHttp\Client;
use OAuth2\OAuth2;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ApiTestCase extends KernelTestCase
{

    protected static $client;
    protected static $access_token;
    protected static $prefijo_api = '/api/v1.0';
    protected static $autor_id;
    protected static function getAuthHeader()
    {
        return 'Bearer ' . self::$access_token;
    }

    protected static function getDefaultOptions()
    {
        return ["headers" => ['Authorization' => self::getAuthHeader()]];
    }

    protected static function createAutor() {
        return self::createUsuario([
            "email" => "autor@test.com",
            "nombre" => "Pedro",
            "apellido" => "Sánchez",
            "googleid" => "2000"
        ]);
    }

    protected static function createUsuario(array $usuarioArray) {
        /** @var ObjectManager $em */
        $em = self::getService('doctrine')->getManager();
        $user = new Usuario();
        $user->setEmail($usuarioArray["email"]);
        $user->setNombre($usuarioArray["nombre"]);
        $user->setApellido($usuarioArray["apellido"]);
        $user->setGoogleid($usuarioArray["googleid"]);
        $role = $em->getRepository(Role::class)->findOneBy(["name" => "ROLE_AUTOR"]);
        $user->addRole($role);
       
        $client = new EntityClient();
        $client->setAllowedGrantTypes(array(OAuth2::GRANT_TYPE_CLIENT_CREDENTIALS));
        $user->setOAuthClient($client);
        $em->persist($client);
        $em->flush();
        $em->persist($user);
        $em->flush();
        return $user;
    }

    public static function setUpBeforeClass(): void
    {
        self::bootKernel();
        self::$client = new Client(
            [
                'base_uri' => 'http://localhost:80/'
            ]
        );

        $autor = self::createAutor();
        self::$autor_id = $autor->getId();
        $client = $autor->getOauthClient();
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
        self::$access_token = $data->access_token;

        $options = [
            'headers' => ['X-AUTH-CREDENTIALS' => true],
            'form_params' => [
                'client_id' => '',
                'client_secret' => ''
            ]
        ];
        $response = self::$client->post('/api/oauth/v2/token', $options);
    }

    public static function tearDownAfterClass() : void
    {
        parent::tearDownAfterClass();
        /** @var ObjectManager $em */
        $em = self::getService('doctrine')->getManager();
        $autor = $em->getRepository(Usuario::class)->find(self::$autor_id);
        self::removeUsuario($autor);
    }

    protected static function removeUsuario($usuario) {
        //TODO: cascade delete
        $em = self::getService('doctrine')->getManager();
        if(!is_null($usuario)){
            $access_tokens = $em->getRepository(AccessToken::class)->findBy(["user" => $usuario->getId()]);
            foreach ($access_tokens as $token) {
                $em->remove($token);
            }
            $em->flush();
            $em->remove($usuario);
            $em->flush();
        }
    }

    protected function tearDown(): void
    {
    }

    protected static function getService($id)
    {
        return self::$kernel->getContainer()->get($id);
    }
}
