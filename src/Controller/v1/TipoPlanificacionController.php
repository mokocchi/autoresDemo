<?php

namespace App\Controller\v1;

use App\Entity\TipoPlanificacion;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/tipos-planificacion")
 */
class TipoPlanificacionController extends AbstractFOSRestController
{
    /**
     * Lists all Tipo Planificacion.
     * @Rest\Get
     *
     * @return Response
     */
    public function getTipoPlanificacionAction()
    {
        $repository = $this->getDoctrine()->getRepository(TipoPlanificacion::class);
        $tipoPlanificacion = $repository->findall();
        return $this->handleView($this->view($tipoPlanificacion));
    }
}
