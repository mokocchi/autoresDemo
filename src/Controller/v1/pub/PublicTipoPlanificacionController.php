<?php

namespace App\Controller\v1\pub;

use App\Controller\BaseController;
use App\Entity\TipoPlanificacion;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/tipos-planificacion")
 */
class PublicTipoPlanificacionController extends BaseController
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
        return $this->handleView($this->getViewWithGroups($tipoPlanificacion, "select"));
    }
}
