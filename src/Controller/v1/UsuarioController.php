<?php

namespace App\Controller\v1;

use App\Controller\BaseController;
use Symfony\Component\Routing\Annotation\Route;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;

/**
 * @Route("/me")
 */
class UsuarioController extends BaseController
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
        $usuario = $this->getUser();
        return $this->handleView($this->getViewWithGroups($usuario, "auth"));
    }
}
