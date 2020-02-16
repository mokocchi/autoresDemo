<?php

namespace App\Controller\v1;

use App\Controller\BaseController;
use FOS\RestBundle\Context\Context;
use Symfony\Component\Routing\Annotation\Route;
use FOS\RestBundle\Controller\Annotations as Rest;

/**
 * @Route("/me")
 */
class UsuarioController extends BaseController
{
    /**
     * @Rest\Get
     */
    public function me()
    {
        $usuario = $this->getUser();
        return $this->handleView($this->getViewWithGroups($usuario, "auth"));
    }
}
