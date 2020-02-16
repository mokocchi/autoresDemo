<?php

namespace App\Controller\v1\pub;

use App\Controller\BaseController;
use App\Entity\Estado;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/estados")
 */
class PublicEstadoController extends BaseController
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
        return $this->handleView($this->getViewWithGroups($estado, "select"));
    }
}
