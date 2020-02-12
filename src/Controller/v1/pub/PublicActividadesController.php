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
    public function getActividadAction()
    {
        $repository = $this->getDoctrine()->getRepository(Actividad::class);
        $estadoRepository = $this->getDoctrine()->getRepository(Estado::class);
        $estado = $estadoRepository->findOneBy(["nombre" => "Público"]);
        $actividades = $repository->findBy(["estado" => $estado]);
        
        $view = $this->view($actividades);
        $context = new Context();
        $context->addGroup('autor');
        $view->setContext($context);
        return $this->handleView($view);
    }
}
