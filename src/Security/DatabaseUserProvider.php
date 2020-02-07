<?php
namespace App\Security;

use App\Entity\Usuario;
use App\Repository\UsuarioRepository;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

class DatabaseUserProvider implements UserProviderInterface
{
    private $userRepository;
    
    public function __construct(UsuarioRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    
    public function loadUserByUsername($username): UserInterface
    {
        print "load"; exit;
        return $this->findUsername($username);
    }

    private function findUsername(string $username): Usuario
    {
        $users = $this->userRepository->findBy(["username" => $username]);
        if (count($users) > 0 ) {
            return $users[0];
        }
        throw new UsernameNotFoundException(
            sprintf('Username "%s" does not exist.', $username)
        );
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof Usuario) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        $username = $user->getUsername();

        return $this->findUsername($username);
    }

    public function supportsClass($class)
    {
        print "supports"; exit;
        return Usuario::class === $class;
    }
}