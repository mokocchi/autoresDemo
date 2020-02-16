<?php

namespace App\Controller\v1\pub;

use App\ApiProblem;
use App\Controller\BaseController;
use App\Entity\TipoPlanificacion;
use Exception;
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
        try{
            $repository = $this->getDoctrine()->getRepository(TipoPlanificacion::class);
            $tipoPlanificacion = $repository->findall();
            return $this->handleView($this->getViewWithGroups(["results" => $tipoPlanificacion], "select"));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->handleView($this->view(
                new ApiProblem(Response::HTTP_INTERNAL_SERVER_ERROR, "Error interno del servidor", "Ocurrió un error"),
                Response::HTTP_INTERNAL_SERVER_ERROR
            ));
        }
        
    }
}
