<?php

namespace App\Controller\v1\pub;

use App\Entity\Actividad;
use App\Entity\Estado;
use App\Entity\Planificacion;
use App\Entity\Salto;
use App\Entity\Tarea;
use App\Form\ActividadType;
use Exception;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use FOS\RestBundle\Context\Context;

/**
 * @Route("/actividades")
 */
class PublicActividadesController extends AbstractFOSRestController
{
    const BIFURCADA_NAME = "Bifurcada";

    /**
     * Lists all Actividad.
     * @Rest\Get
     *
     * @return Response
     */
    public function getActividadesAction()
    {
        $repository = $this->getDoctrine()->getRepository(Actividad::class);
        $estadoRepository = $this->getDoctrine()->getRepository(Estado::class);
        $estado = $estadoRepository->findOneBy(["nombre" => "Público"]);
        $actividades = $repository->findBy(["estado" => $estado]);
        
        $view = $this->view($actividades);
        $context = new Context();
        $context->addGroup('publico');
        $view->setContext($context);
        return $this->handleView($view);
    }


    /**
     * Shows an Actividad.
     * @Rest\Get("/{id}")
     *
     * @return Response
     */
    public function showActividadAction($id)
    {
        $repository = $this->getDoctrine()->getRepository(Actividad::class);
        $actividad = $repository->find($id);
        if (is_null($actividad)) {
            return $this->handleView($this->view(['errors' => 'Objeto no encontrado'], Response::HTTP_NOT_FOUND));
        }
        if ($actividad->getEstado()->getNombre() == "Privado") {
            return $this->handleView($this->view(['errors' => 'La actividad es privada'], Response::HTTP_UNAUTHORIZED));
        }

        $view = $this->view($actividad);
        $context = new Context();
        $context->addGroup('publico');
        $view->setContext($context);
        return $this->handleView($view);
    }
}
