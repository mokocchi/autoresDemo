<?php

namespace App\Controller\v1\pub;

use App\Controller\BaseController;
use App\Entity\TipoTarea;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/tipos-tarea")
 */
class PublicTiposTareaController extends BaseController
{
    /**
     * Lists all TipoTarea.
     * @Rest\Get
     *
     * @return Response
     */
    public function getTipoTareaAction()
    {
        $repository = $this->getDoctrine()->getRepository(TipoTarea::class);
        $tipostarea = $repository->findall();
        return $this->handleView($this->getViewWithGroups($tipostarea, "select"));
    }
}
