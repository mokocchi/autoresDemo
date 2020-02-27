<?php

namespace App\DataFixtures;

use App\Entity\Client;
use App\Entity\Role;
use App\Entity\Usuario;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use OAuth2\OAuth2;

class UserFixture extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $user = new Usuario();
        $user->setEmail("autor@autores.demo");
        $user->setNombre("Autor");
        $user->setApellido("Autórez");
        $user->setGoogleid("20000");
        $role = $manager->getRepository(Role::class)->findOneBy(["name" => "ROLE_AUTOR"]);
        $user->addRole($role);

        $client = new Client();
        $client->setAllowedGrantTypes(array(OAuth2::GRANT_TYPE_CLIENT_CREDENTIALS));
        $user->setOAuthClient($client);
        $manager->persist($client);
        $manager->flush();
        $manager->persist($user);
        $manager->flush();
    }
}