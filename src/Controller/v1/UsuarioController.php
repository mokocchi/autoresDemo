<?php

namespace App\Controller\v1;

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\Routing\Annotation\Route;
use FOS\RestBundle\Controller\Annotations as Rest;

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

        $view = $this->view($usuario);
        $context = new Context();
        $context->addGroup('auth');
        $view->setContext($context);
        return $this->handleView($view);
    }
}
