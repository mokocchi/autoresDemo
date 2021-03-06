<?php

namespace App\Controller\v1\pub;

use App\Controller\BaseController;
use App\Entity\Estado;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;

/**
 * @Route("/estados")
 */
class PublicEstadosController extends BaseController
{

    /**
     * Lista todos los estados
     * @Rest\Get(name="get_estados")
     * 
     * @SWG\Response(
     *     response=200,
     *     description="Operación exitosa"
     * )
     *
     * @SWG\Response(
     *     response=500,
     *     description="Error en el servidor"
     * )
     * 
     * @SWG\Tag(name="Estado")
     * @return Response
     */
    public function getEstadoAction()
    {
        $repository = $this->getDoctrine()->getRepository(Estado::class);
        $estado = $repository->findall();
        return $this->handleView($this->getViewWithGroups(["results" => $estado], "select"));
    }
}
