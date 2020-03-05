<?php

namespace App\Controller\v1\pub;

use App\Controller\BaseController;
use App\Entity\Actividad;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/planificaciones")
 */
class PublicPlanificacionesController extends BaseController
{

    private function checkActividadFound($id)
    {
        return $this->checkEntityFound(Actividad::class, $id);
    }

    /**
     * Muestra la planificación de una actividad
     * @Rest\Get("/{id}", name="get_planificacion_actividad_public")
     *
     * @SWG\Parameter(
     *     required=true,
     *     name="Authorization",
     *     in="header",
     *     type="string",
     *     description="Bearer token",
     * )
     * 
     * @SWG\Response(
     *     response=401,
     *     description="No autorizado"
     * )
     * 
     * @SWG\Response(
     *     response=403,
     *     description="Permisos insuficientes"
     * )
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
     * @SWG\Parameter(
     *     required=true,
     *     name="id",
     *     in="path",
     *     type="string",
     *     description="Id de la actividad",
     *     schema={}
     * )
     * 
     * @SWG\Tag(name="Planificación")
     * @return Response
     */
    public function getActividadPlanificacionAction($id)
    {
        $actividad = $this->checkActividadFound($id);
        $planificacion = $actividad->getPlanificacion();
        return $this->handleView($this->getViewWithGroups($planificacion, "publico"));
    }
}
