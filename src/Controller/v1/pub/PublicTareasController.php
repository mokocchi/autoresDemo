<?php

namespace App\Controller\v1\pub;

use App\ApiProblem;
use App\Controller\BaseController;
use App\Entity\Estado;
use App\Entity\Tarea;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;

/**
 * @Route("/tareas")
 */
class PublicTareasController extends BaseController
{
    /**
     * Lista todas las tareaas
     * @Rest\Get(name="get_tareas_public")
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
     * @SWG\Tag(name="Tarea")
     * @return Response
     */
    public function getTareasAction()
    {
        try {
            $repository = $this->getDoctrine()->getRepository(Tarea::class);
            $estadoRepository = $this->getDoctrine()->getRepository(Estado::class);
            $estado = $estadoRepository->findOneBy(["nombre" => "Público"]);
            $tareas = $repository->findBy(["estado" => $estado]);
            return $this->handleView($this->getViewWithGroups($tareas, "publico"));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->handleView($this->view(
                new ApiProblem(Response::HTTP_INTERNAL_SERVER_ERROR, "Error interno del servidor", "Ocurrió un error"),
                Response::HTTP_INTERNAL_SERVER_ERROR
            ));
        }
    }

    /**
     * Shows a Tarea.
     * @Rest\Get("/{id}", name="show_tarea_public")
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
     * @SWG\Tag(name="Tarea")
     * @return Response
     */
    public function showTareaAction($id)
    {
        try {
            $repository = $this->getDoctrine()->getRepository(Tarea::class);
            $tarea = $repository->find($id);
            if (is_null($tarea)) {
                return $this->handleView($this->view(['errors' => 'Objeto no encontrado'], Response::HTTP_NOT_FOUND));
            }
            if ($tarea->getEstado()->getNombre() == "Privado") {
                return $this->handleView($this->view(['errors' => 'La tarea es privada'], Response::HTTP_UNAUTHORIZED));
            }

            return $this->handleView($this->getViewWithGroups($tarea, "publico"));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->handleView($this->view(
                new ApiProblem(Response::HTTP_INTERNAL_SERVER_ERROR, "Error interno del servidor", "Ocurrió un error"),
                Response::HTTP_INTERNAL_SERVER_ERROR
            ));
        }
    }
}
