<?php

namespace App\Controller\v1;

use App\ApiProblem;
use App\Controller\BaseController;
use App\Entity\Actividad;
use App\Entity\Dominio;
use App\Entity\Estado;
use App\Entity\Idioma;
use App\Entity\Planificacion;
use App\Entity\Salto;
use App\Entity\Tarea;
use App\Entity\TipoPlanificacion;
use App\Form\ActividadType;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Swagger\Annotations as SWG;

/**
 * @Route("/actividades")
 */
class ActividadesController extends BaseController
{
    const BIFURCADA_NAME = "Bifurcada";

    /**
     * Lista todas las actividades del sistema
     * @Rest\Get(name="get_actividades")
     * @IsGranted("ROLE_ADMIN")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Operación exitosa"
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
     * @SWG\Tag(name="Actividad")
     * @return Response
     */
    public function getActividadesAction()
    {
        try {
            $repository = $this->getDoctrine()->getRepository(Actividad::class);
            $actividades = $repository->findBy(["autor" => $this->getUser()]);
            return $this->handleView($this->getViewWithGroups(["results" => $actividades], "autor"));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->handleView($this->view(
                new ApiProblem(Response::HTTP_INTERNAL_SERVER_ERROR, "Error interno del servidor", "Ocurrió un error"),
                Response::HTTP_INTERNAL_SERVER_ERROR
            ));
        }
    }

    /**
     * Lista las actividades del usuario actual
     * 
     * @Rest\Get("/user", name="get_actividades_user")
     * @IsGranted("ROLE_AUTOR")
     * 
     * @SWG\Response(
     *     response=200,
     *     description="Operación exitosa"
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
     *     response=500,
     *     description="Error en el servidor"
     * )
     * 
     * @SWG\Parameter(
     *     name="Authorization",
     *     required=true,
     *     in="header",
     *     type="string",
     *     description="Bearer token",
     * )
     * 
     * @SWG\Tag(name="Actividad")
     * @return Response
     */
    public function getActividadesForUserAction()
    {
        try {
            $user = $this->getUser();
            $repository = $this->getDoctrine()->getRepository(Actividad::class);
            $actividades = $repository->findBy(["autor" => $user]);
            return $this->handleView($this->getViewWithGroups(["results" => $actividades], "autor"));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->handleView($this->view(
                new ApiProblem(Response::HTTP_INTERNAL_SERVER_ERROR, "Error interno del servidor", "Ocurrió un error"),
                Response::HTTP_INTERNAL_SERVER_ERROR
            ));
        }
    }

    /**
     * Muestra una actividad
     * @Rest\Get("/{id}", name="show_actividad")
     * @IsGranted("ROLE_AUTOR")
     *
     * @SWG\Response(
     *     response=401,
     *     description="No autorizado"
     * )
     * 
     * @SWG\Response(
     *     response=403,
     *     description="Permisos insuficientes o La actividad es privada"
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
     * @SWG\Response(
     *     response=200,
     *     description="Operación exitosa"
     * )
     * 
     * @SWG\Response(
     *     response=404,
     *     description="Actividad no encontrada"
     * )
     *
     * @SWG\Response(
     *     response=500,
     *     description="Error en el servidor"
     * )
     * 
     * @SWG\Parameter(
     *     required=true,
     *     name="id",
     *     in="path",
     *     type="string",
     *     description="Id de la actividad",
     *     schema={}
     * )
     * 
     * @SWG\Tag(name="Actividad")
     * @return Response
     */
    public function showActividadAction($id)
    {
        try {
            $repository = $this->getDoctrine()->getRepository(Actividad::class);
            $actividad = $repository->find($id);
            if (is_null($actividad)) {
                return $this->handleView($this->view(
                    new ApiProblem(Response::HTTP_NOT_FOUND, "El id no corresponde a ninguna actividad", "No se encontró la actividad"),
                    Response::HTTP_NOT_FOUND
                ));
            }
            if ($actividad->getEstado()->getNombre() == "Privado" && $actividad->getAutor()->getId() !== $this->getUser()->getId()) {
                return $this->handleView($this->view(
                    new ApiProblem(Response::HTTP_UNAUTHORIZED, "La actividad es privada o no pertenece al usuario actual", "No se puede acceder a la actividad"),
                    Response::HTTP_UNAUTHORIZED
                ));
            }
            return $this->handleView($this->getViewWithGroups($actividad, "autor"));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->handleView($this->view(
                new ApiProblem(Response::HTTP_INTERNAL_SERVER_ERROR, "Error interno del servidor", "Ocurrió un error"),
                Response::HTTP_INTERNAL_SERVER_ERROR
            ));
        }
    }

    /**
     * Crea una actividad
     * @Rest\Post(name="post_actividad")
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
     *     response=201,
     *     description="La actividad fue creada"
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
     * @SWG\Parameter(
     *     required=true,
     *     name="nombre",
     *     in="body",
     *     type="string",
     *     description="Nombre de la actividad",
     *     schema={}
     * )
     * 
     * @SWG\Parameter(
     *     required=true,
     *     name="objetivo",
     *     in="body",
     *     type="string",
     *     description="Objetivo de la actividad",
     *     schema={}
     * )
     *
     * @SWG\Parameter(
     *     required=true,
     *     name="dominio",
     *     in="body",
     *     type="integer",
     *     description="Id del dominio de la actividad",
     *     schema={}
     * )
     * 
     * @SWG\Parameter(
     *     required=true,
     *     name="idioma",
     *     in="body",
     *     type="integer",
     *     description="Id del idioma de la actividad",
     *     schema={}
     * )
     * 
     * @SWG\Parameter(
     *     required=true,
     *     name="tipoPlanificacion",
     *     in="body",
     *     type="integer",
     *     description="Id del tipo de planificación de la actividad",
     *     schema={}
     * )
     * 
     * @SWG\Parameter(
     *     required=true,
     *     name="estado",
     *     in="body",
     *     type="integer",
     *     description="Id del estado de la actividad",
     *     schema={}
     * )
     * 
     * @SWG\Tag(name="Actividad")
     * 
     * @return Response
     */
    public function postActividadAction(Request $request)
    {
        try {
            $actividad = new Actividad();
            $form = $this->createForm(ActividadType::class, $actividad);
            $data = json_decode($request->getContent(), true);
            if (is_null($data)) {
                return $this->handleView($this->view(
                    new ApiProblem(Response::HTTP_BAD_REQUEST, "No hay campos json en el request", "No se puede crear una actividad con datos vacíos"),
                    Response::HTTP_BAD_REQUEST
                ));
            }
            $form->submit($data);
            if ($form->isSubmitted() && $form->isValid()) {
                if (
                    !array_key_exists("nombre", $data) ||
                    is_null($data["nombre"]) ||
                    !array_key_exists("objetivo", $data) ||
                    is_null($data["objetivo"]) ||
                    !array_key_exists("dominio", $data) ||
                    is_null($data["dominio"]) ||
                    !array_key_exists("idioma", $data) ||
                    is_null($data["idioma"]) ||
                    !array_key_exists("tipoPlanificacion", $data) ||
                    is_null($data["tipoPlanificacion"]) ||
                    !array_key_exists("estado", $data) ||
                    is_null($data["objetivo"])
                ) {
                    return $this->handleView($this->view(
                        new ApiProblem(Response::HTTP_BAD_REQUEST, "Uno o más de los campos requeridos falta o es nulo", "Faltan datos"),
                        Response::HTTP_BAD_REQUEST
                    ));
                }
                $em = $this->getDoctrine()->getManager();
                $planificacion = new Planificacion();
                $em->persist($planificacion);
                $em->flush();
                $actividad->setPlanificacion($planificacion);
                $actividad->setAutor($this->getUser());
                $em->persist($actividad);
                $em->flush();

                $url = $this->generateUrl('show_actividad', ['id' => $actividad->getId()]);
                return $this->handleView($this->setGroupToView($this->view($actividad, Response::HTTP_CREATED, ["Location" => $url]), "autor"));
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

    /**
     * Actualiza una actividad
     * @Rest\Put("/{id}",name="put_actividad")
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
     *     response=404,
     *     description="Actividad no encontrada"
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
     * @SWG\Parameter(
     *     name="nombre",
     *     in="body",
     *     type="string",
     *     description="Nombre de la actividad",
     *     schema={}
     * )
     * 
     * @SWG\Parameter(
     *     name="objetivo",
     *     in="body",
     *     type="string",
     *     description="Objetivo de la actividad",
     *     schema={}
     * )
     *
     * @SWG\Parameter(
     *     name="dominio",
     *     in="body",
     *     type="integer",
     *     description="Id del dominio de la actividad",
     *     schema={}
     * )
     * 
     * @SWG\Parameter(
     *     name="idioma",
     *     in="body",
     *     type="integer",
     *     description="Id del idioma de la actividad",
     *     schema={}
     * )
     * 
     * @SWG\Parameter(
     *     name="tipoPlanificacion",
     *     in="body",
     *     type="integer",
     *     description="Id del tipo de planificación de la actividad",
     *     schema={}
     * )
     * 
     * @SWG\Parameter(
     *     name="estado",
     *     in="body",
     *     type="integer",
     *     description="Id del estado de la actividad",
     *     schema={}
     * )
     * 
     * @SWG\Tag(name="Actividad")
     * 
     * @return Response
     */
    public function putActividadAction(Request $request, $id)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $actividadRepository = $em->getRepository(Actividad::class);
            /** @var Actividad $actividad */
            $actividad = $actividadRepository->find($id);
            if ($actividad->getAutor() != $this->getUser()) {
                return $this->handleView($this->view(
                    new ApiProblem(Response::HTTP_UNAUTHORIZED, "La actividad no pertenece al usuario actual", "No se puede acceder a la actividad"),
                    Response::HTTP_UNAUTHORIZED
                ));
            }
            $data = json_decode($request->getContent(), true);
            if (is_null($data)) {
                return $this->handleView($this->view(
                    new ApiProblem(Response::HTTP_BAD_REQUEST, "No hay campos json en el request", "No se puede crear una actividad con datos vacíos"),
                    Response::HTTP_BAD_REQUEST
                ));
            }
            if (array_key_exists("nombre", $data) && !is_null($data["nombre"])) {
                $actividad->setNombre($data["nombre"]);
            }
            if (array_key_exists("objetivo", $data) && !is_null($data["objetivo"])) {
                $actividad->setObjetivo($data["objetivo"]);
            }

            if (array_key_exists("dominio", $data) && !is_null($data["dominio"])) {
                $dominio = $em->getRepository(Dominio::class)->find($data["dominio"]);
                $actividad->setDominio($dominio);
            }

            if (array_key_exists("idioma", $data) && !is_null($data["idioma"])) {
                $idioma = $em->getRepository(Idioma::class)->find($data["idioma"]);
                $actividad->setIdioma($idioma);
            }

            if (array_key_exists("tipoPlanificacion", $data) && !is_null($data["tipoPlanificacion"])) {
                /** @var TipoPlanificacion */
                $tipoPlanificacion = $em->getRepository(TipoPlanificacion::class)->find($data["tipoPlanificacion"]);
                $actividad->setTipoPlanificacion($tipoPlanificacion);
                if ($tipoPlanificacion->getNombre() != self::BIFURCADA_NAME) {
                    $planificacion = $actividad->getPlanificacion();
                    $saltos = $planificacion->getSaltos();
                    foreach ($saltos as $salto) {
                        $planificacion->removeSalto($salto);
                    }
                }
            }

            if (array_key_exists("estado", $data) && !is_null($data["estado"])) {
                $estado = $em->getRepository(Estado::class)->find($data["estado"]);
                $actividad->setEstado($estado);
            }
            $em->persist($actividad);
            $em->flush();
            return $this->handleView($this->getViewWithGroups($actividad, "autor"));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->handleView($this->view(
                new ApiProblem(Response::HTTP_INTERNAL_SERVER_ERROR, "Error interno del servidor", "Ocurrió un error"),
                Response::HTTP_INTERNAL_SERVER_ERROR
            ));
        }
    }

    /**
     * Elimina una actividad
     * @Rest\Delete("/{id}",name="delete_actividad")
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
     *     response=404,
     *     description="Actividad no encontrada"
     * )
     * 
     * @SWG\Response(
     *     response=204,
     *     description="La actividad fue borrada"
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
     * @SWG\Tag(name="Actividad")
     * 
     * @return Response
     */
    public function deleteActividadAction($id)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $actividad = $em->getRepository(Actividad::class)->find($id);
            if ($actividad->getAutor() != $this->getUser()) {
                return $this->handleView($this->view(
                    new ApiProblem(Response::HTTP_UNAUTHORIZED, "La actividad no pertenece al usuario actual", "No se puede acceder a la actividad"),
                    Response::HTTP_UNAUTHORIZED
                ));
            }
            $em->remove($actividad);
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

    /**
     * Asigna una tarea a una actividad
     * @Rest\Post("/{id}/tareas", name="post_tarea_actividad")
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
     *     description="La operación fue exitosa"
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
     * @SWG\Parameter(
     *     required=true,
     *     name="id",
     *     in="path",
     *     type="string",
     *     description="Id de la actividad",
     *     schema={}
     * )
     * 
     * @SWG\Parameter(
     *     required=true,
     *     name="tarea",
     *     in="body",
     *     type="string",
     *     description="Id de la tarea",
     *     schema={}
     * )
     * 
     * @SWG\Tag(name="Actividad")
     * @return Response
     */
    public function addTareaToActividad(Request $request, $id)
    {
        $data = json_decode($request->getContent(), true);
        if (!array_key_exists("tarea", $data) && !is_null($data["tarea"])) {
            return $this->handleView($this->view(
                new ApiProblem(Response::HTTP_BAD_REQUEST, "Uno o más de los campos requeridos falta o es nulo", "Faltan datos"),
                Response::HTTP_BAD_REQUEST
            ));
        }

        $em = $this->getDoctrine()->getManager();

        try {
            $tarea = $em->getRepository(Tarea::class)->find($data["tarea"]);
            $actividad = $em->getRepository(Actividad::class)->find($id);
            if (is_null($tarea)) {
                return $this->handleView($this->view(
                    new ApiProblem(Response::HTTP_NOT_FOUND, "El id no corresponde a ninguna tarea", "No se encontró la tarea"),
                    Response::HTTP_NOT_FOUND
                ));
            }
            if (is_null($actividad)) {
                return $this->handleView($this->view(
                    new ApiProblem(Response::HTTP_NOT_FOUND, "El id no corresponde a ninguna actividad", "No se encontró la actividad"),
                    Response::HTTP_NOT_FOUND
                ));
            }
            $actividad->addTarea($tarea);
            $em->persist($actividad);
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
     * Lista las tareas de una actividad
     * @Rest\Get("/{id}/tareas", name="get_actividad_tareas")
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
     *     required=true,
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
     * @SWG\Parameter(
     *     required=true,
     *     name="id",
     *     in="path",
     *     type="string",
     *     description="Id de la actividad",
     *     schema={}
     * )
     * 
     * @SWG\Tag(name="Actividad")
     * @return Response
     */
    public function getActividadTareasAction(Request $request, $id)
    {
        try {
            $repository = $this->getDoctrine()->getRepository(Actividad::class);
            $actividad = $repository->find($id);
            if (!is_null($actividad)) {
                $tareas = $actividad->getTareas();
                return $this->handleView($this->getViewWithGroups(["results" => $tareas], "autor"));
            } else {
                return $this->handleView($this->view(
                    new ApiProblem(Response::HTTP_NOT_FOUND, "El id no corresponde a ninguna actividad", "No se encontró la actividad"),
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

    /**
     * Lista todos los saltos de una actividad
     * @Rest\Get("/{id}/saltos", name="get_actividad_saltos")
     * @IsGranted("ROLE_AUTOR")
     *
     * @SWG\Parameter(
     *     required=true,
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
     * @SWG\Parameter(
     *     required=true,
     *     name="id",
     *     in="path",
     *     type="string",
     *     description="Id de la actividad",
     *     schema={}
     * )
     * 
     * @SWG\Tag(name="Actividad")
     * @return Response
     */
    public function getActividadSaltosAction($id)
    {
        try {
            $repository = $this->getDoctrine()->getRepository(Actividad::class);
            $actividad = $repository->find($id);
            if (is_null($actividad)) {
                return $this->handleView($this->view(
                    new ApiProblem(Response::HTTP_NOT_FOUND, "El id no corresponde a ninguna actividad", "No se encontró la actividad"),
                    Response::HTTP_NOT_FOUND
                ));
            }
            $planificacion = $actividad->getPlanificacion();
            $saltos = $planificacion->getSaltos();
            return $this->handleView($this->getViewWithGroups(["results" => $saltos], "autor"));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->handleView($this->view(
                new ApiProblem(Response::HTTP_INTERNAL_SERVER_ERROR, "Error interno del servidor", "Ocurrió un error"),
                Response::HTTP_INTERNAL_SERVER_ERROR
            ));
        }
    }

    /**
     * Crea un salto para una actividad
     * @Rest\Post("/{id}/saltos", name="post_saltos_actividad")
     * @IsGranted("ROLE_AUTOR")
     *
     * @SWG\Parameter(
     *     required=true,
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
     * @SWG\Parameter(
     *     required=true,
     *     name="id",
     *     in="path",
     *     type="string",
     *     description="Id de la actividad",
     *     schema={}
     * )
     * 
     * @SWG\Parameter(
     *     required=true,
     *     name="origen",
     *     in="body",
     *     type="string",
     *     description="Id de la tarea origen del salto",
     *     schema={}
     * )
     * 
     * @SWG\Parameter(
     *     required=true,
     *     name="condicion",
     *     in="body",
     *     type="string",
     *     description="Condición del salto",
     *     schema={}
     * )
     * 
     * @SWG\Parameter(
     *     name="destinos",
     *     in="body",
     *     type="array",
     *     description="Ids de las tareas destino del salto",
     *     schema={}
     * )
     * 
     * @SWG\Parameter(
     *     name="respuesta",
     *     in="body",
     *     type="string",
     *     description="Ids de las tareas destino del salto",
     *     schema={}
     * )
     * 
     * @SWG\Tag(name="Actividad")
     * @return Response
     */
    public function postSaltoForActividadAction(Request $request, $id)
    {
        $salto = new Salto();
        $data = json_decode($request->getContent(), true);

        if (!array_key_exists("origen", $data) || !array_key_exists("condicion", $data)) {
            return $this->handleView($this->view(
                new ApiProblem(Response::HTTP_BAD_REQUEST, "Uno o más de los campos requeridos falta o es nulo", "Faltan datos"),
                Response::HTTP_BAD_REQUEST
            ));
        }

        try {
            $em = $this->getDoctrine()->getManager();
            $actividad = $em->getRepository(Actividad::class)->find($id);
            if (is_null($actividad)) {
                return $this->handleView($this->view(
                    new ApiProblem(Response::HTTP_NOT_FOUND, "El id no corresponde a ninguna actividad", "No se encontró la actividad"),
                    Response::HTTP_NOT_FOUND
                ));
            }
            $planificacion = $actividad->getPlanificacion();
            $salto->setPlanificacion($planificacion);
            $tareaRepository = $em->getRepository(Tarea::class);
            $origen = $tareaRepository->find($data["origen"]);
            if (is_null($origen)) {
                return $this->handleView($this->view(
                    new ApiProblem(Response::HTTP_NOT_FOUND, "El id de tarea origen no corresponde a ninguna tarea", "No se encontró la tarea origen"),
                    Response::HTTP_NOT_FOUND
                ));
            }
            $salto->setOrigen($origen);
            if (array_key_exists("destinos", $data) && !is_null($data["destinos"])) {
                foreach ($data["destinos"] as $destino_id) {
                    $tareaDb = $tareaRepository->find($destino_id);
                    if (is_null($tareaDb)) {
                        return $this->handleView($this->view(
                            new ApiProblem(Response::HTTP_NOT_FOUND, "El id de la tarea destino no corresponde a ninguna tarea", "No se encontró la tarea destino"),
                            Response::HTTP_NOT_FOUND
                        ));
                    }
                    $salto->addDestino($tareaDb);
                }
            } //TODO: existen saltos sin destino? por qué esto no es required?
            $salto->setCondicion($data["condicion"]);
            if (array_key_exists("respuesta", $data) && !is_null($data["respuesta"])) {
                $salto->setRespuesta($data["respuesta"]);
            }

            $em->persist($salto);
            $em->flush();
            $url = $this->generateUrl("show_salto", ["id" => $salto->getId()]);
            $view = $this->view($salto, Response::HTTP_CREATED, ["Location" => $url]);
            return $this->handleView($this->setGroupToView($view, "autor"));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->handleView($this->view(
                new ApiProblem(Response::HTTP_INTERNAL_SERVER_ERROR, "Error interno del servidor", "Ocurrió un error"),
                Response::HTTP_INTERNAL_SERVER_ERROR
            ));
        }
    }

    /**
     * Borra todos los saltos de una actividad
     * @Rest\Delete("/{id}/saltos", name="delete_saltos")
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
     *     required=true,
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
     *     response=404,
     *     description="Actividad no encontrada"
     * )
     *
     * @SWG\Response(
     *     response=500,
     *     description="Error en el servidor"
     * )
     * 
     * @SWG\Parameter(
     *     required=true,
     *     name="id",
     *     in="path",
     *     type="string",
     *     description="Id de la actividad",
     *     schema={}
     * )
     * 
     * @SWG\Tag(name="Actividad")
     * @return Response
     */
    public function deleteSaltosAction($id)
    {
        try {
            $repository = $this->getDoctrine()->getRepository(Actividad::class);
            $actividad = $repository->find($id);
            if (is_null($actividad)) {
                return $this->handleView($this->view(
                    new ApiProblem(Response::HTTP_NOT_FOUND, "El id no corresponde a ninguna actividad", "No se encontró la actividad"),
                    Response::HTTP_NOT_FOUND
                ));
            }
            $planificacion = $actividad->getPlanificacion();
            $saltos = $planificacion->getSaltos();
            $em = $this->getDoctrine()->getManager();
            foreach ($saltos as $salto) {
                $em->remove($salto);
            }
            $em->persist($planificacion);
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
     * Desasigna todas las tareas de una actividad
     * @Rest\Delete("/{id}/tareas", name="detach_tareas_actividad")
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
     *     required=true,
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
     *     response=404,
     *     description="Actividad no encontrada"
     * )
     *
     * @SWG\Response(
     *     response=500,
     *     description="Error en el servidor"
     * )
     * 
     * @SWG\Parameter(
     *     required=true,
     *     name="id",
     *     in="path",
     *     type="string",
     *     description="Id de la actividad",
     *     schema={}
     * )
     * 
     * @SWG\Tag(name="Actividad")
     * @return Response
     */
    public function deleteTareasAction($id)
    {
        try {
            $repository = $this->getDoctrine()->getRepository(Actividad::class);
            $actividad = $repository->find($id);
            if (is_null($actividad)) {
                return $this->handleView($this->view(
                    new ApiProblem(Response::HTTP_NOT_FOUND, "El id no corresponde a ninguna actividad", "No se encontró la actividad"),
                    Response::HTTP_NOT_FOUND
                ));
            }
            $tareas = $actividad->getTareas();
            foreach ($tareas as $tarea) {
                $actividad->removeTarea($tarea);
            }
            $em = $this->getDoctrine()->getManager();
            $em->persist($actividad);
            $planificacion = $actividad->getPlanificacion();
            $saltos = $planificacion->getSaltos();
            $em = $this->getDoctrine()->getManager();
            foreach ($saltos as $salto) {
                $em->remove($salto);
            }
            $em->persist($planificacion);
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
     * Agrega una configuración de planifiación a una actividad
     * @Rest\Post("/{id}/planificaciones", name="post_planificacion_settings_actividad")
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
     *     required=true,
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
     *     response=404,
     *     description="Actividad no encontrada"
     * )
     *
     * @SWG\Response(
     *     response=500,
     *     description="Error en el servidor"
     * )
     * 
     * @SWG\Parameter(
     *     required=true,
     *     name="id",
     *     in="path",
     *     type="string",
     *     description="Id de la actividad",
     *     schema={}
     * )
     * 
     * @SWG\Parameter(
     *     required=true,
     *     name="iniciales",
     *     in="body",
     *     type="array",
     *     description="Ids de las tareas iniciales de la actividad",
     *     schema={}
     * )
     * 
     * @SWG\Parameter(
     *     required=true,
     *     name="opcionales",
     *     in="body",
     *     type="array",
     *     description="Ids de las tareas opcionales de la actividad",
     *     schema={}
     * )
     * 
     * @SWG\Tag(name="Actividad")
     * @return Response
     */
    public function updatePlanificacionSettings(Request $request, $id)
    {
        $data = json_decode($request->getContent(), true);

        if (
            !array_key_exists("iniciales", $data) || !array_key_exists("opcionales", $data)
        ) {
            return $this->handleView($this->view(
                new ApiProblem(Response::HTTP_BAD_REQUEST, "Uno o más de los campos requeridos falta o es nulo", "Faltan datos"),
                Response::HTTP_BAD_REQUEST
            ));
        }

        try {
            $repository = $this->getDoctrine()->getRepository(Actividad::class);
            $actividad = $repository->find($id);
            if (is_null($actividad)) {
                return $this->handleView($this->view(
                    new ApiProblem(Response::HTTP_NOT_FOUND, "El id no corresponde a ninguna actividad", "No se encontró la actividad"),
                    Response::HTTP_NOT_FOUND
                ));
            }
            $planificacion = $actividad->getPlanificacion();
            $prevOpcionales = $planificacion->getOpcionales();
            foreach ($prevOpcionales as $opcional) {
                $planificacion->removeOpcional($opcional);
            }
            $prevIniciales = $planificacion->getIniciales();
            foreach ($prevIniciales as $inicial) {
                $planificacion->removeInicial($inicial);
            }

            $iniciales = $data["iniciales"];
            $tareaRepository = $this->getDoctrine()->getRepository(Tarea::class);
            foreach ($iniciales as $inicial) {
                $tareaInicial = $tareaRepository->find($inicial);
                if (is_null($tareaInicial)) {
                    return $this->handleView($this->view(
                        new ApiProblem(Response::HTTP_NOT_FOUND, "El id de una tarea inicial no corresponde a ninguna tarea", "No se encontró una tarea inicial"),
                        Response::HTTP_NOT_FOUND
                    ));
                }
                $planificacion->addInicial($tareaInicial);
            }
            $opcionales = $data["opcionales"];
            foreach ($opcionales as $opcional) {
                $tareaOpcional = $tareaRepository->find($opcional);
                if (is_null($tareaOpcional)) {
                    return $this->handleView($this->view(
                        new ApiProblem(Response::HTTP_NOT_FOUND, "El id de una tarea opcional no corresponde a ninguna tarea", "No se encontró una tarea opcional"),
                        Response::HTTP_NOT_FOUND
                    ));
                }
                $planificacion->addOpcional($tareaOpcional);
            }
            $em = $this->getDoctrine()->getManager();
            $em->persist($planificacion);
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
}
