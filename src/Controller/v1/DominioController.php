<?php

namespace App\Controller\v1;

use App\ApiProblem;
use App\Controller\BaseController;
use App\Entity\Dominio;
use App\Form\DominioType;
use Exception;
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
     *     response=401,
     *     description="No autorizado"
     * )
     * 
     * @SWG\Response(
     *     response=403,
     *     description="Permisos insuficientes"
     * )
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
        try {
            $dominio = new Dominio();
            $form = $this->createForm(DominioType::class, $dominio);
            $data = json_decode($request->getContent(), true);
            if (is_null($data)) {
                return $this->handleView($this->view(
                    new ApiProblem(Response::HTTP_BAD_REQUEST, "No hay campos json en el request", "No se puede crear una actividad con datos vacíos"),
                    Response::HTTP_BAD_REQUEST
                ));
            }
            if (!array_key_exists("nombre", $data) || is_null($data["nombre"])) {
                return $this->handleView($this->view(
                    new ApiProblem(Response::HTTP_BAD_REQUEST, "Uno o más de los campos requeridos falta o es nulo", "Faltan datos para crear la actividad"),
                    Response::HTTP_BAD_REQUEST
                ));
            }
            $form->submit($data);
            if ($form->isSubmitted() && $form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $dominioDb = $em->getRepository(Dominio::class)->findOneBy(["nombre" => $data["nombre"]]);
                if (!is_null($dominioDb)) {
                    $url = $this->generateUrl("show_dominio", ["id" => $dominioDb->getId()]);
                    return $this->handleView($this->setGroupToView($this->view($dominio, Response::HTTP_OK, ["Location" => $url]), "autor"));
                }
                $em->persist($dominio);
                $em->flush();
                $url = $this->generateUrl("show_dominio", ["id" => $dominio->getId()]);
                print_r($dominio);
                exit;
                return $this->handleView($this->setGroupToView($this->view($dominio, Response::HTTP_CREATED, ["Location" => $url]), "autor"));
            } else {
                $this->logger->alert("Datos inválidos: " . json_decode($form->getErrors()));
                return $this->handleView($this->view(
                    new ApiProblem(Response::HTTP_BAD_REQUEST, "Se recibieron datos inválidos", "Datos inválidos"),
                    Response::HTTP_BAD_REQUEST
                ));
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->handleView($this->view(
                new ApiProblem(Response::HTTP_INTERNAL_SERVER_ERROR, "Error interno del servidor", "Ocurrió un error"),
                Response::HTTP_INTERNAL_SERVER_ERROR
            ));
        }
    }
}
