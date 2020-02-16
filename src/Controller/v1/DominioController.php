<?php

namespace App\Controller\v1;

use App\Controller\BaseController;
use App\Entity\Dominio;
use App\Form\DominioType;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Swagger\Annotations as SWG;

/**
 * @Route("/dominios")
 */
class DominioController extends BaseController
{
    /**
     * Crea un dominio.
     * @Rest\Post(name="post_dominio")
     * @IsGranted("ROLE_AUTOR")
     *
     * @SWG\Response(
     *     response=200,
     *     description="El dominio ya existe"
     * )
     * 
     * @SWG\Response(
     *     response=201,
     *     description="El dominio fue creado"
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="Hubo un problema con la petición"
     * )
     * 
     * @SWG\Response(
     *     response=500,
     *     description="Error en el servidor"
     * )
     *
     * @SWG\Parameter(
     *     name="Authorization",
     *     in="header",
     *     type="string",
     *     description="Bearer token",
     * )
     *
     * @SWG\Parameter(
     *     name="nombre",
     *     in="body",
     *     type="string",
     *     description="Nombre del dominio",
     *     schema={}
     * )
     * 
     * @SWG\Tag(name="Dominio")
     * @return Response
     */
    public function postDominioAction(Request $request)
    {
        $dominio = new Dominio();
        $form = $this->createForm(DominioType::class, $dominio);
        $data = json_decode($request->getContent(), true);
        if(is_null($data)) {
            return $this->handleView($this->view(['errors' => 'No hay campos en el request'], Response::HTTP_BAD_REQUEST));
        }
        if(!array_key_exists("nombre",$data) || is_null($data["nombre"])) {
            return $this->handleView($this->view(['errors' => 'Faltan campos en el request'], Response::HTTP_BAD_REQUEST));
        }
        $form->submit($data);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $dominioDb = $em->getRepository(Dominio::class)->findOneBy(["nombre" => $data["nombre"]]);
            if (!is_null($dominioDb)) {
            $url = $this->generateUrl("show_dominio", ["id" => $dominioDb->getId()]);
                return $this->handleView($this->view(null, Response::HTTP_OK, ["Location" => $url]));
            }
            $em->persist($dominio);
            $em->flush();
            $url = $this->generateUrl("show_dominio", ["id" => $dominio->getId()]);
            return $this->handleView($this->view(null, Response::HTTP_CREATED, ["Location" => $url]));
        }
        return $this->handleView($this->view($form->getErrors()));
    }
}
