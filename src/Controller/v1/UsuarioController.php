<?php

namespace App\Controller\v1;

use App\Entity\AccessToken;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\Routing\Annotation\Route;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/me")
 */
class UsuarioController extends AbstractFOSRestController
{
    /**
     * @Rest\Get
     */
    public function me()
    {
        $usuario = $this->getUser();
        return $this->handleView($this->view($usuario));
    }
}