<?php

namespace App\Controller\v1\pub;

use App\Entity\Dominio;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/dominios")
 */
class PublicDominioController extends AbstractFOSRestController
{

    /**
     * Lists all Dominio.
     * @Rest\Get
     *
     * @return Response
     */
    public function getDominioAction()
    {
        $repository = $this->getDoctrine()->getRepository(Dominio::class);
        $dominio = $repository->findall();
        return $this->handleView($this->view($dominio));
    }
}
