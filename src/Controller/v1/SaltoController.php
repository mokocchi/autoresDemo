<?php

namespace App\Controller\v1;

use App\Controller\BaseController;
use App\Entity\Salto;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Swagger\Annotations as SWG;

/**
 * @Route("/saltos")
 */
class SaltoController extends BaseController
{

    /**
     * Muestra un salto
     * @Rest\Get("/{id}", name="show_salto")
     * @IsGranted("ROLE_AUTOR")
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
     * @SWG\Parameter(
     *     required=true,
     *     name="Authorization",
     *     in="header",
     *     type="string",
     *     description="Bearer token",
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
     *     description="Id del salto",
     *     schema={}
     * )
     * 
     * @SWG\Tag(name="Salto")
     * @return Response
     */
    public function showSalto($id) {
        try {
            $saltoRepository = $this->getDoctrine()->getRepository(Salto::class);
            $salto = $saltoRepository->find($id);
            return $this->getViewWithGroups($salto, "autor");
        } catch (Exception $e) {
            return $this->handleView($this->view([$e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR));
        }
    }
}
