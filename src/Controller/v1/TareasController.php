<?php

namespace App\Controller\v1;

use App\ApiProblem;
use App\ApiProblemException;
use App\Controller\BaseController;
use App\Entity\Plano;
use App\Entity\Tarea;
use App\Form\TareaType;
use App\Service\UploaderHelper;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Swagger\Annotations as SWG;

/**
 * @Route("/tareas")
 */
class TareasController extends BaseController
{
    private function checkAccessTarea($tarea)
    {
        if ($tarea->getEstado()->getNombre() == "Privado" && $tarea->getAutor()->getId() !== $this->getUser()->getId()) {
            throw new ApiProblemException(
                new ApiProblem(Response::HTTP_FORBIDDEN, "La tarea es privada o no pertenece al usuario actual", "No se puede acceder a la tarea")
            );
        }
    }

    public function checkOwnTarea($tarea)
    {
        if ($tarea->getAutor()->getId() !== $this->getUser()->getId()) {
            throw new ApiProblemException(
                new ApiProblem(Response::HTTP_FORBIDDEN, "La tarea no pertenece al usuario actual", "No se puede acceder a la tarea"),
            );
        }
    }

    public function checkCodigoNotUsed($codigo)
    {
        $this->checkPropertyNotUsed(Tarea::class, "codigo", $codigo, "Ya existe una tarea con el mismo código");
    }

    private function checkTareaFound($id)
    {
        return $this->checkEntityFound(Tarea::class, $id);
    }

    /**
     * Lista todas las tareas del sistema
     * @Rest\Get(name="get_tareas")
     * @IsGranted("ROLE_ADMIN")
     * 
     * @SWG\Parameter(
     *     name="Authorization",
     *     in="header",
     *     type="string",
     *     description="Bearer token",
     * )
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
     *     description="Operación exitosa"
     * )
     *
     * @SWG\Response(
     *     response=500,
     *     description="Error en el servidor"
     * )
     * 
     * @SWG\Tag(name="Tarea")
     * @return Response
     */
    public function getTareasAction()
    {
        $repository = $this->getDoctrine()->getRepository(Tarea::class);
        $tareas = $repository->findall();
        return $this->handleView($this->getViewWithGroups(["results" => $tareas], "autor"));
    }

    /**
     * Lista las tareas del usuario actual
     * 
     * @Rest\Get("/user", name="get_tareas_user")
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
     * @SWG\Parameter(
     *     name="Authorization",
     *     in="header",
     *     type="string",
     *     description="Bearer token",
     * )
     * 
     * @SWG\Response(
     *     response=200,
     *     description="Operación exitosa"
     * )
     *
     * @SWG\Response(
     *     response=500,
     *     description="Error en el servidor"
     * )
     * 
     * @SWG\Tag(name="Tarea")
     * @return Response
     */
    public function getActividadForUserAction()
    {
        $user = $this->getUser();
        $repository = $this->getDoctrine()->getRepository(Tarea::class);
        $tareas = $repository->findBy(["autor" => $user]);
        return $this->handleView($this->getViewWithGroups(["results" => $tareas], "autor"));
    }


    /**
     * Crear Tarea
     * @Rest\Post(name="post_tarea")
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
     *     description="La tarea ya existe"
     * )
     * 
     * @SWG\Response(
     *     response=201,
     *     description="La tarea fue creada"
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
     *     description="Nombre de la tarea",
     *     schema={}
     * )
     * 
     * @SWG\Parameter(
     *     name="consigna",
     *     in="body",
     *     type="string",
     *     description="Consigna de la tarea",
     *     schema={}
     * )
     *
     * @SWG\Parameter(
     *     name="codigo",
     *     in="body",
     *     type="integer",
     *     description="Codigo de la tarea",
     *     schema={}
     * )
     * 
     * @SWG\Parameter(
     *     name="tipo",
     *     in="body",
     *     type="integer",
     *     description="Tipo de tarea",
     *     schema={}
     * )
     * 
     * @SWG\Parameter(
     *     name="dominio",
     *     in="body",
     *     type="integer",
     *     description="Dominio de la tarea",
     *     schema={}
     * )
     * 
     * @SWG\Parameter(
     *     name="estado",
     *     in="body",
     *     type="integer",
     *     description="Id del estado de la tarea",
     *     schema={}
     * )
     * 
     * @SWG\Tag(name="Tarea")
     * @return Response
     */
    public function postTareaAction(Request $request)
    {
        $tarea = new Tarea();
        $form = $this->createForm(TareaType::class, $tarea);
        $data = $this->getJsonData($request);
        $this->checkRequiredParameters([
            "nombre",
            "consigna",
            "codigo",
            "tipo",
            "dominio",
            "estado"
        ], $data);
        $form->submit($data);
        $this->checkFormValidity($form);
        $this->checkCodigoNotUsed($data["codigo"]);

        $tarea->setAutor($this->getUser());
        $em = $this->getDoctrine()->getManager();
        $em->persist($tarea);
        $em->flush();
        $url = $this->generateUrl("show_tarea", ["id" => $tarea->getId()]);
        return $this->handleView($this->setGroupToView($this->view($tarea, Response::HTTP_CREATED, ["Location" => $url]), "autor"));
    }

    /**
     * Muestra una tarea
     * @Rest\Get("/{id}", name="show_tarea")
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
     * @SWG\Parameter(
     *     name="Authorization",
     *     in="header",
     *     type="string",
     *     description="Bearer token",
     * )
     * 
     * @SWG\Response(
     *     response=200,
     *     description="Operación exitosa"
     * )
     *
     * @SWG\Response(
     *     response=500,
     *     description="Error en el servidor"
     * )
     * 
     * @SWG\Tag(name="Tarea")
     * @return Response
     */
    public function showTareaAction($id)
    {
        $tarea = $this->checkTareaFound($id);
        $this->checkAccessTarea($tarea);
        return $this->handleView($this->getViewWithGroups($tarea, "autor"));
    }



    /**
     * Agrega el extra a una tarea
     * @Rest\Post("/{id}/extra", name="post_extra_tarea")
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
     *     description="Operación exitosa"
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="Hubo un problema con la petición"
     * )
     * 
     * @SWG\Response(
     *     response=404,
     *     description="No se encontró la tarea"
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
     *     name="extra",
     *     in="body",
     *     type="array",
     *     description="Contenido extra de la tarea",
     *     schema={}
     * )
     * 
     * @SWG\Tag(name="Tarea")
     * @return Response
     */
    public function updateExtraOnTareaAction(Request $request, $id)
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!array_key_exists("extra", $data) && !is_null($data["extra"])) {
                return $this->handleView($this->view(
                    new ApiProblem(Response::HTTP_BAD_REQUEST, "Uno o más de los campos requeridos falta o es nulo", "Faltan datos"),
                    Response::HTTP_BAD_REQUEST
                ));
            }

            $em = $this->getDoctrine()->getManager();

            $extra = $data["extra"];
            $tarea = $em->getRepository(Tarea::class)->find($id);
            if (is_null($tarea)) {
                return $this->handleView($this->view(
                    new ApiProblem(Response::HTTP_NOT_FOUND, "El id no corresponde a ninguna tarea", "No se encontró la tarea"),
                    Response::HTTP_NOT_FOUND
                ));
            }
            $tarea->setExtra($extra);
            $em->persist($tarea);
            $em->flush();
            return $this->handleView($this->view(['status' => 'ok'], Response::HTTP_OK));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->handleView($this->view(
                new ApiProblem(Response::HTTP_INTERNAL_SERVER_ERROR, "Error interno del servidor", "Ocurrió un error"),
                Response::HTTP_INTERNAL_SERVER_ERROR
            ));
        }
    }

    /**
     * Agregar plano a una tarea
     * @Rest\Post("/{id}/plano", name="post_plano_tarea")
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
     *     description="Operación exitosa"
     * )
     * 
     * @SWG\Response(
     *     response=400,
     *     description="Hubo un problema con la petición"
     * )
     * 
     * @SWG\Response(
     *     response=404,
     *     description="No se encontró la tarea"
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
     *     name="extra",
     *     in="formData",
     *     type="file",
     *     description="Plano de la tarea",
     *     schema={}
     * )
     * 
     * @SWG\Tag(name="Tarea")
     * @return Response
     */
    public function updateMapOnTareaAction(Request $request, $id, UploaderHelper $uploaderHelper, ValidatorInterface $validator)
    {
        try {
            if (!$request->files->has('plano')) {
                return $this->handleView($this->view(['errors' => 'No se encontró el archivo'], Response::HTTP_BAD_REQUEST));
            }
            $plano = new Plano();
            $uploadedFile = $request->files->get('plano');
            $plano->setPlano($uploadedFile);

            $errors = $validator->validate($plano);

            if (count($errors) > 0) {
                $this->logger->alert("Archivo inválido: " . json_decode($errors));
                return $this->handleView($this->view(
                    new ApiProblem(Response::HTTP_BAD_REQUEST, "Se recibió una imagen inválida", "Imagen inválida"),
                    Response::HTTP_BAD_REQUEST
                ));
            }
            $em = $this->getDoctrine()->getManager();
            $tarea = $em->getRepository(Tarea::class)->find($id);

            if (!is_null($tarea)) {
                $uploaderHelper->uploadPlano($uploadedFile, $tarea->getCodigo(), false);
                return $this->handleView($this->view(['status' => 'ok'], Response::HTTP_OK));
            } else {
                return $this->handleView($this->view(
                    new ApiProblem(Response::HTTP_NOT_FOUND, "El id no corresponde a ninguna tarea", "No se encontró la tarea"),
                    Response::HTTP_NOT_FOUND
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
