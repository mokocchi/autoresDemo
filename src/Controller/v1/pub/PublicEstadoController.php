<?php

namespace App\Controller\v1\pub;

use App\Controller\BaseController;
use App\Entity\Estado;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;

/**
 * @Route("/estados")
 */
class PublicEstadoController extends BaseController
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
        try {
            $repository = $this->getDoctrine()->getRepository(Estado::class);
            $estado = $repository->findall();
            return $this->handleView($this->getViewWithGroups($estado, "select"));
        } catch (Exception $e) {
            return $this->handleView($this->view(["errors" => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR));
        }
    }
}
