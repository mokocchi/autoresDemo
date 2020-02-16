<?php

namespace App\Controller\v1\pub;

use App\Controller\BaseController;
use App\Entity\Idioma;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/idiomas")
 */
class PublicIdiomasController extends BaseController
{
    /**
     * Lists all Idiomas.
     * @Rest\Get
     *
     * @return Response
     */
    public function getIdiomaAction()
    {
        $repository = $this->getDoctrine()->getRepository(Idioma::class);
        $idiomas = $repository->findall();
        return $this->handleView($this->getViewWithGroups($idiomas, "select"));
    }
}
