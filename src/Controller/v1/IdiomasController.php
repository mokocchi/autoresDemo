<?php

namespace App\Controller\v1;

use App\Entity\Idioma;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/idiomas")
 */
class IdiomasController extends AbstractFOSRestController
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
        return $this->handleView($this->view($idiomas));
    }
}
