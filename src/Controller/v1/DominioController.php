<?php

namespace App\Controller\v1;

use App\Entity\Dominio;
use App\Form\DominioType;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/dominios")
 */
class DominioController extends AbstractFOSRestController
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

    /**
     * Create Dominio.
     * @Rest\Post
     *
     * @return Response
     */
    public function postDominioAction(Request $request)
    {
        $dominio = new Dominio();
        $form = $this->createForm(DominioType::class, $dominio);
        $data = json_decode($request->getContent(), true);
        $form->submit($data);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $dominioDb = $em->getRepository(Dominio::class)->findBy(["nombre" => $data["nombre"]]);
            if (!empty($dominioDb)) {
                return $this->handleView($this->view($dominioDb[0], Response::HTTP_OK));
            }
            $em->persist($dominio);
            $em->flush();
            return $this->handleView($this->view($dominio, Response::HTTP_CREATED));
        }
        return $this->handleView($this->view($form->getErrors()));
    }
}