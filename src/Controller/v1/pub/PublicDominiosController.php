<?php

namespace App\Controller\v1\pub;

use App\ApiProblem;
use App\Controller\BaseController;
use App\Entity\Dominio;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/dominios")
 */
class PublicDominiosController extends BaseController
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
     * SWG\Parameter(
     *     name="nombre",
     *     in="query",
     *     type="string",
     *     description="Id del dominio",
     *     schema={}
     * )
     * 
     * @SWG\Tag(name="Dominio")
     * @return Response
     */
    public function getDominiosAction(Request $request)
    {
        try {
            $repository = $this->getDoctrine()->getRepository(Dominio::class);
            $nombre = $request->query->get("nombre");
            if($nombre) {
                $dominios = $repository->findBy(["nombre" => $nombre]);
            } else {
                $dominios = $repository->findall();
            }
            return $this->handleView($this->getViewWithGroups(["results" => $dominios], "select"));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->handleView($this->view(
                new ApiProblem(Response::HTTP_INTERNAL_SERVER_ERROR, "Error interno del servidor", "Ocurrió un error"),
                Response::HTTP_INTERNAL_SERVER_ERROR
            ));
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
     * SWG\Parameter(
     *     required=true,
     *     name="id",
     *     in="path",
     *     type="string",
     *     description="Id del dominio",
     *     schema={}
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
            return $this->handleView($this->getViewWithGroups($dominio, "publico"));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->handleView($this->view(
                new ApiProblem(Response::HTTP_INTERNAL_SERVER_ERROR, "Error interno del servidor", "Ocurrió un error"),
                Response::HTTP_INTERNAL_SERVER_ERROR
            ));
        }
    }
}