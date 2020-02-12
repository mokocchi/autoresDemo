<?php

namespace App\Controller\v1\pub;

use App\Entity\Estado;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/estados")
 */
class PublicEstadoController extends AbstractFOSRestController
{

    /**
     * Lists all Estados.
     * @Rest\Get
     *
     * @return Response
     */
    public function getEstadoAction()
    {
        $repository = $this->getDoctrine()->getRepository(Estado::class);
        $estado = $repository->findall();
        return $this->handleView($this->view($estado));
    }
}
