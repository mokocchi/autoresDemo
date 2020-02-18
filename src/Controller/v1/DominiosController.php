<?php

namespace App\Controller\v1;

use App\ApiProblem;
use App\ApiProblemException;
use App\Controller\BaseController;
use App\Entity\Actividad;
use App\Entity\Dominio;
use App\Entity\Tarea;
use App\Form\DominioType;
use App\Repository\TareaRepository;
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
class DominiosController extends BaseController
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
        if (is_null($data)) {
            throw new ApiProblemException(
                new ApiProblem(Response::HTTP_BAD_REQUEST, "No hay campos en el request", "Hubo un problema con la petición")
            );
        }
        if (!array_key_exists("nombre", $data) || is_null($data["nombre"])) {
            throw new ApiProblemException(
                new ApiProblem(Response::HTTP_BAD_REQUEST, "Uno o más de los campos requeridos falta o es nulo", "Faltan datos para crear la actividad")
            );
        }
        $form->submit($data);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $dominioDb = $em->getRepository(Dominio::class)->findOneBy(["nombre" => $data["nombre"]]);
            if (!is_null($dominioDb)) {
                throw new ApiProblemException(
                    new ApiProblem(Response::HTTP_BAD_REQUEST, "Ya existe un dominio con el mismo nombre", "Ya existe un dominio con el mismo nombre")
                );
            }
            $em->persist($dominio);
            $em->flush();
            $url = $this->generateUrl("show_dominio", ["id" => $dominio->getId()]);
            return $this->handleView($this->setGroupToView($this->view($dominio, Response::HTTP_CREATED, ["Location" => $url]), "select"));
        } else {
            $this->logger->alert("Datos inválidos: " . json_decode($form->getErrors()));
            throw new ApiProblemException(
                new ApiProblem(Response::HTTP_BAD_REQUEST, "Se recibieron datos inválidos", "Datos inválidos"),
            );
        }
    }

    /**
     * Elimina un dominio
     * @Rest\Delete("/{id}",name="delete_dominio")
     * @IsGranted("ROLE_ADMIN")
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
     *     response=404,
     *     description="Dominio no encontrado"
     * )
     * 
     * @SWG\Response(
     *     response=204,
     *     description="El dominio fue borrado"
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
     *     required=true,
     *     name="Authorization",
     *     in="header",
     *     type="string",
     *     description="Bearer token",
     * )
     *
     * @SWG\Tag(name="Dominio")
     * 
     * @return Response
     */
    public function deleteDominioAction($id)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $dominio = $em->getRepository(Dominio::class)->find($id);
            /** @var TareaRepository $em */
            $tareaRepository = $em->getRepository(Tarea::class);
            if ($tareaRepository->isThereWithDominio($dominio)) {
                return $this->handleView($this->view(
                    new ApiProblem(Response::HTTP_BAD_REQUEST, "No se puede borrar el dominio: hay tareas asociadas", "No se puede borrar el dominio porque hay tareas que lo usan"),
                    Response::HTTP_BAD_REQUEST
                ));
            }
            $actividadRepository = $em->getRepository(Actividad::class);
            if ($actividadRepository->isThereWithDominio($dominio)) {
                return $this->handleView($this->view(
                    new ApiProblem(Response::HTTP_BAD_REQUEST, "No se puede borrar el dominio: hay actividades asociadas", "No se puede borrar el dominio porque hay actividades que lo usan"),
                    Response::HTTP_BAD_REQUEST
                ));
            }
            $em->remove($dominio);
            $em->flush();
            $this->handleView($this->view(null, Response::HTTP_NO_CONTENT));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->handleView($this->view(
                new ApiProblem(Response::HTTP_INTERNAL_SERVER_ERROR, "Error interno del servidor", "Ocurrió un error"),
                Response::HTTP_INTERNAL_SERVER_ERROR
            ));
        }
    }
}
