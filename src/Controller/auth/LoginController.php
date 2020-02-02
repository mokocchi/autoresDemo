<?php

namespace App\Controller\auth;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\Routing\Annotation\Route;
use FOS\RestBundle\Controller\Annotations as Rest;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Swagger\Annotations as SWG;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/login")
 */
class LoginController extends AbstractFOSRestController
{
    /**
     * @Rest\Get
     *
     * @SWG\Response(
     *     response=201,
     *     description="User was successfully logged in"
     * )
     * 
     * @SWG\Response(
     *     response=403,
     *     description="Invalid credentials"
     * )
     *
     * @SWG\Parameter(
     *     name="X-AUTH-TOKEN",
     *     in="header",
     *     type="string",
     *     description="google id_token",
     * )
     *
     * @SWG\Tag(name="User")
     */
    public function login(UserInterface $user, JWTTokenManagerInterface $JWTManager)
    {
        return new JsonResponse(['token' => $JWTManager->create($user)]);
    }
}
