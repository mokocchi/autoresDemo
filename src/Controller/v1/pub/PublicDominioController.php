<?php

namespace App\Controller\v1\pub;

use App\Entity\Dominio;
use Exception;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;

/**
 * @Route("/dominios")
 */
class PublicDominioController extends AbstractFOSRestController
{

    /**
     * Lista todos los dominios
     * @Rest\Get(name="get_dominios")
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
     * @SWG\Tag(name="Dominio")
     * @return Response
     */
    public function getDominiosAction()
    {
        try {
            $repository = $this->getDoctrine()->getRepository(Dominio::class);
            $dominios = $repository->findall();
            return $this->handleView($this->view($dominios));
        } catch (Exception $e) {
            return $this->handleView($this->view(["errors" => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR));
        }
    }

    /**
     * Muestra un dominio
     * @Rest\Get("/{id}", name="show_dominio")
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
     * @SWG\Tag(name="Dominio")
     * @return Response
     */
    public function showDominioAction($id)
    {
        try {
            $repository = $this->getDoctrine()->getRepository(Dominio::class);
            $dominio = $repository->find($id);
            return $this->handleView($this->view($dominio));
        } catch (Exception $e) {
            return $this->handleView($this->view(["errors" => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR));
        }
    }
}
