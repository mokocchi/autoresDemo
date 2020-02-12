<?php

namespace App\Controller\v1\pub;

use App\Entity\Estado;
use App\Entity\Tarea;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/tareas")
 */
class PublicTareasController extends AbstractFOSRestController
{
    /**
     * Lists all Tarea.
     * @Rest\Get
     * 
     * @return Response
     */
    public function getTareasAction()
    {
        $repository = $this->getDoctrine()->getRepository(Tarea::class);
        $estadoRepository = $this->getDoctrine()->getRepository(Estado::class);
        $estado = $estadoRepository->findOneBy(["nombre" => "Público"]);
        $tareas = $repository->findBy(["estado" => $estado]);
        
        $view = $this->view($tareas);
        $context = new Context();
        $context->addGroup('publico');
        $view->setContext($context);
        return $this->handleView($view);
    }

    /**
     * Shows a Tarea.
     * @Rest\Get("/{id}")
     *
     * @return Response
     */
    public function showTareaAction($id)
    {
        $repository = $this->getDoctrine()->getRepository(Tarea::class);
        $tarea = $repository->find($id);
        if (is_null($tarea)) {
            return $this->handleView($this->view(['errors' => 'Objeto no encontrado'], Response::HTTP_NOT_FOUND));
        }
        if ($tarea->getEstado()->getNombre() == "Privado") {
            return $this->handleView($this->view(['errors' => 'La tarea es privada'], Response::HTTP_UNAUTHORIZED));
        }
        
        $view = $this->view($tarea);
        $context = new Context();
        $context->addGroup('publico');
        $view->setContext($context);
        return $this->handleView($view);
    }
}
