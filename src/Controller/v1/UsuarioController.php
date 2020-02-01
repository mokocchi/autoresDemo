<?php

namespace App\Controller\v1;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/usuario", name="usuario")
 */
class UsuarioController extends AbstractFOSRestController
{
    public function index()
    {
        return $this->render('usuario/index.html.twig', [
            'controller_name' => 'UsuarioController',
        ]);
    }
}
