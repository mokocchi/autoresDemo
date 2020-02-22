<?php

namespace App\Controller\v1;

use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Controller\BaseController;
use Exception;
use Symfony\Component\Routing\Annotation\Route;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/me")
 */
class MeController extends BaseController
{
    /**
     * @Rest\Get(name="get_me")
     * 
     * @SWG\Response(
     *     response=401,
     *     description="No autorizado"
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
     *     name="Authorization",
     *     in="header",
     *     type="string",
     *     description="Bearer token",
     * )
     * 
     * @SWG\Tag(name="Usuario")
     */
    public function me()
    {
        try {
            $usuario = $this->getUser();
            return $this->handleView($this->getViewWithGroups($usuario, "auth"));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            throw new ApiProblemException(
                new ApiProblem(Response::HTTP_INTERNAL_SERVER_ERROR, "Error interno del servidor", "Ocurrió un error")
            );
        }
    }
}
