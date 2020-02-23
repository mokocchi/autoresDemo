<?php

namespace App\Controller\v1\pub;

use App\Controller\BaseController;
use App\Entity\TipoPlanificacion;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;

/**
 * @Route("/tipos-planificacion")
 */
class PublicTipoPlanificacionController extends BaseController
{
    /**
     * Lista todos los tipos de planificación
     * @Rest\Get(name="get_tipos_planificacion")
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
     * @SWG\Tag(name="Tipo planificación")
     * @return Response
     */
    public function getTipoPlanificacionAction()
    {
        $repository = $this->getDoctrine()->getRepository(TipoPlanificacion::class);
        $tipoPlanificacion = $repository->findall();
        return $this->handleView($this->getViewWithGroups(["results" => $tipoPlanificacion], "select"));
    }
}
