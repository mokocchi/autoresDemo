<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use App\Entity\Actividad;
use App\Entity\Idioma;
use App\Entity\Dominio;
use App\Entity\Planificacion;
use App\Entity\Salto;
use App\Entity\Tarea;
use App\Entity\TipoPlanificacion;
use App\Entity\TipoTarea;
use App\Form\ActividadType;
use App\Form\DominioType;
use App\Form\TareaType;
use Exception;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/")
 */
class IndexController extends AbstractFOSRestController
{
    const BIFURCADA_NAME = "Bifurcada";

    /**
     * Lists all Idiomas.
     * @Rest\Get("/idiomas")
     *
     * @return Response
     */
    public function getIdiomaAction()
    {
        $repository = $this->getDoctrine()->getRepository(Idioma::class);
        $idiomas = $repository->findall();
        return $this->handleView($this->view($idiomas));
    }

    /**
     * Lists all Tipo Planificacion.
     * @Rest\Get("/tipos-planificacion")
     *
     * @return Response
     */
    public function getTipoPlanificacionAction()
    {
        $repository = $this->getDoctrine()->getRepository(TipoPlanificacion::class);
        $tipoPlanificacion = $repository->findall();
        return $this->handleView($this->view($tipoPlanificacion));
    }

    /**
     * Lists all Dominio.
     * @Rest\Get("/dominios")
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
     * @Rest\Post("/dominio")
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

    /**
     * Lists all Actividad.
     * @Rest\Get("/actividades")
     *
     * @return Response
     */
    public function getActividadAction()
    {
        $repository = $this->getDoctrine()->getRepository(Actividad::class);
        $actividades = $repository->findall();
        return $this->handleView($this->view($actividades));
    }

    /**
     * Shows an Actividad.
     * @Rest\Get("/actividad/{id}")
     *
     * @return Response
     */
    public function showActividadAction($id)
    {
        $repository = $this->getDoctrine()->getRepository(Actividad::class);
        $actividad = $repository->find($id);
        if (is_null($actividad)) {
            return $this->handleView($this->view(['errors' => 'Objeto no encontrado'], Response::HTTP_NOT_FOUND));
        }
        return $this->handleView($this->view($actividad));
    }

    /**
     * Create Actividad.
     * @Rest\Post("/actividad")
     *
     * @return Response
     */
    public function postActividadAction(Request $request)
    {
        $actividad = new Actividad();
        $form = $this->createForm(ActividadType::class, $actividad);
        $data = json_decode($request->getContent(), true);
        $form->submit($data);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em = $this->getDoctrine()->getManager();
                $em->persist($actividad);
                $em->flush();
                return $this->handleView($this->view($actividad, Response::HTTP_CREATED));
            } catch (Exception $e) {
                return $this->handleView($this->view(["errors" => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR));
            }
        }
        return $this->handleView($this->view($form->getErrors(), Response::HTTP_INTERNAL_SERVER_ERROR));
    }

    /**
     * Update idioma on Actividad.
     * @Rest\Post("/actividad/{id}/idioma")
     *
     * @return Response
     */
    public function updateIdiomaOnActividadAction(Request $request, $id)
    {
        $data = json_decode($request->getContent(), true);
        $em = $this->getDoctrine()->getManager();

        try {
            if (!array_key_exists("idioma", $data)) {
                return $this->handleView($this->view(['errors' => 'Faltan campos en el request'], Response::HTTP_UNPROCESSABLE_ENTITY));
            }
            $idioma = $em->getRepository(Idioma::class)->find($data["idioma"]);
            $actividad = $em->getRepository(Actividad::class)->find($id);
            if (!is_null($idioma) && !is_null($actividad)) {
                $actividad->setIdioma($idioma);
                $em->persist($actividad);
                $em->flush();
                return $this->handleView($this->view(['status' => 'ok'], Response::HTTP_OK));
            } else {
                return $this->handleView($this->view(['errors' => 'Objeto no encontrado'], Response::HTTP_NOT_FOUND));
            }
        } catch (Exception $e) {
            return $this->handleView($this->view([$e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR));
        }
    }

    /**
     * Update dominio on Actividad.
     * @Rest\Post("/actividad/{id}/dominio")
     *
     * @return Response
     */
    public function updateDominoOnActividadAction(Request $request, $id)
    {
        $data = json_decode($request->getContent(), true);
        $em = $this->getDoctrine()->getManager();

        try {
            if (!array_key_exists("dominio", $data)) {
                return $this->handleView($this->view(['errors' => 'Faltan campos en el request'], Response::HTTP_UNPROCESSABLE_ENTITY));
            }
            $dominio = $em->getRepository(Dominio::class)->find($data["dominio"]);
            $actividad = $em->getRepository(Actividad::class)->find($id);
            if (!is_null($dominio) && !is_null($actividad)) {
                $actividad->setDominio($dominio);
                $em->persist($actividad);
                $em->flush();
                return $this->handleView($this->view(['status' => 'ok'], Response::HTTP_OK));
            } else {
                return $this->handleView($this->view(['errors' => 'Objeto no encontrado'], Response::HTTP_NOT_FOUND));
            }
        } catch (Exception $e) {
            return $this->handleView($this->view([$e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR));
        }
    }

    /**
     * Update tipo planificacion on Actividad.
     * @Rest\Post("/actividad/{id}/tipo-planificacion")
     *
     * @return Response
     */
    public function updatePlanificacionOnActividadAction(Request $request, $id)
    {
        $data = json_decode($request->getContent(), true);
        $em = $this->getDoctrine()->getManager();

        try {
            if (!array_key_exists("tipo-planificacion", $data)) {
                return $this->handleView($this->view(['errors' => 'Faltan campos en el request'], Response::HTTP_UNPROCESSABLE_ENTITY));
            }
            $tipoPlanificacion = $em->getRepository(TipoPlanificacion::class)->find($data["tipo-planificacion"]);
            $actividad = $em->getRepository(Actividad::class)->find($id);
            if (!is_null($tipoPlanificacion) && !is_null($actividad)) {
                if ($tipoPlanificacion->getNombre() == self::BIFURCADA_NAME) {
                    $planificacion = new Planificacion();
                    $em->persist($planificacion);
                    $em->flush();
                    $actividad->setPlanificacion($planificacion);
                }
                $actividad->setTipoPlanificacion($tipoPlanificacion);
                $em->persist($actividad);
                $em->flush();
                return $this->handleView($this->view(['status' => 'ok'], Response::HTTP_OK));
            } else {
                return $this->handleView($this->view(['errors' => 'Objeto no encontrado'], Response::HTTP_NOT_FOUND));
            }
        } catch (Exception $e) {
            return $this->handleView($this->view([$e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR));
        }
    }

    /**
     * Lists all Tarea.
     * @Rest\Get("/tareas")
     *
     * @return Response
     */
    public function getTareasAction()
    {
        $repository = $this->getDoctrine()->getRepository(Tarea::class);
        $tareas = $repository->findall();
        return $this->handleView($this->view($tareas));
    }

    /**
     * Lists all TipoTarea.
     * @Rest\Get("/tipos-tarea")
     *
     * @return Response
     */
    public function getTipoTareaAction()
    {
        $repository = $this->getDoctrine()->getRepository(TipoTarea::class);
        $tipostarea = $repository->findall();
        return $this->handleView($this->view($tipostarea));
    }

    /**
     * Create Tarea.
     * @Rest\Post("/tarea")
     *
     * @return Response
     */
    public function postTareaAction(Request $request)
    {
        $tarea = new Tarea();
        $form = $this->createForm(TareaType::class, $tarea);
        $data = json_decode($request->getContent(), true);
        $form->submit($data);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em = $this->getDoctrine()->getManager();
                $tareaDb = $em->getRepository(Tarea::class)->findBy(["codigo" => $data["codigo"]]);
                if (!empty($tareaDb)) {
                    return $this->handleView($this->view($tareaDb[0], Response::HTTP_OK));
                }
                $em->persist($tarea);
                $em->flush();
                return $this->handleView($this->view($tarea, Response::HTTP_CREATED));
            } catch (Exception $e) {
                return $this->handleView($this->view(["errors" => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR));
            }
        }
        return $this->handleView($this->view($form->getErrors(), Response::HTTP_INTERNAL_SERVER_ERROR));
    }

    /**
     * Update tipo on Tarea.
     * @Rest\Post("/tarea/{id}/tipo-tarea")
     *
     * @return Response
     */
    public function updateTipoOnTareaAction(Request $request, $id)
    {
        $data = json_decode($request->getContent(), true);
        if (!array_key_exists("tipo-tarea", $data)) {
            return $this->handleView($this->view(['errors' => 'Faltan campos en el request'], Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        $em = $this->getDoctrine()->getManager();

        try {
            $tipoTarea = $em->getRepository(TipoTarea::class)->find($data["tipo-tarea"]);
            $tarea = $em->getRepository(Tarea::class)->find($id);
            if (!is_null($tipoTarea) && !is_null($tarea)) {
                $tarea->setTipo($tipoTarea);
                $em->persist($tarea);
                $em->flush();
                return $this->handleView($this->view(['status' => 'ok'], Response::HTTP_OK));
            } else {
                return $this->handleView($this->view(['errors' => 'Objeto no encontrado'], Response::HTTP_NOT_FOUND));
            }
        } catch (Exception $e) {
            return $this->handleView($this->view([$e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR));
        }
    }

    /**
     * Update dominio on Tarea.
     * @Rest\Post("/tarea/{id}/dominio")
     *
     * @return Response
     */
    public function updateDominioOnTareaAction(Request $request, $id)
    {
        $data = json_decode($request->getContent(), true);
        if (!array_key_exists("dominio", $data)) {
            return $this->handleView($this->view(['errors' => 'Faltan campos en el request'], Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        $em = $this->getDoctrine()->getManager();

        try {
            $dominio = $em->getRepository(Dominio::class)->find($data["dominio"]);
            $tarea = $em->getRepository(Tarea::class)->find($id);
            if (!is_null($dominio) && !is_null($tarea)) {
                $tarea->setDominio($dominio);
                $em->persist($tarea);
                $em->flush();
                return $this->handleView($this->view(['status' => 'ok'], Response::HTTP_OK));
            } else {
                return $this->handleView($this->view(['errors' => 'Objeto no encontrado'], Response::HTTP_NOT_FOUND));
            }
        } catch (Exception $e) {
            return $this->handleView($this->view([$e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR));
        }
    }

    /**
     * Shows a Tarea.
     * @Rest\Get("/tarea/{id}")
     *
     * @return Response
     */
    public function showTareaAction($id)
    {
        $repository = $this->getDoctrine()->getRepository(Tarea::class);
        $tarea = $repository->find($id);
        if (is_null($tarea)) {
            return $this->handleView($this->view(['errors' => 'Objeto no encontrado'], Response::HTTP_NOT_FOUND));
        }
        return $this->handleView($this->view($tarea));
    }

    /**
     * Add a Tarea to an Activity.
     * @Rest\Post("/actividad/{id}/tarea")
     *
     * @return Response
     */
    public function addTareaToActividad(Request $request, $id)
    {
        $data = json_decode($request->getContent(), true);
        if (!array_key_exists("tarea", $data)) {
            return $this->handleView($this->view(['errors' => 'Faltan campos en el request'], Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        $em = $this->getDoctrine()->getManager();

        try {
            $tarea = $em->getRepository(Tarea::class)->find($data["tarea"]);
            $actividad = $em->getRepository(Actividad::class)->find($id);
            if (!is_null($tarea) && !is_null($actividad)) {
                $actividad->addTarea($tarea);
                $em->persist($actividad);
                $em->flush();
                return $this->handleView($this->view(['status' => 'ok'], Response::HTTP_OK));
            } else {
                return $this->handleView($this->view(['errors' => 'Objeto no encontrado'], Response::HTTP_NOT_FOUND));
            }
        } catch (Exception $e) {
            return $this->handleView($this->view([$e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR));
        }
    }

    /**
     * Update extra on Tarea.
     * @Rest\Post("/tarea/{id}/extra")
     *
     * @return Response
     */
    public function updateExtraOnTareaAction(Request $request, $id)
    {
        $data = json_decode($request->getContent(), true);
        if (!array_key_exists("extra", $data)) {
            return $this->handleView($this->view(['errors' => 'Faltan campos en el request'], Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        $em = $this->getDoctrine()->getManager();

        try {
            $extra = $data["extra"];
            $tarea = $em->getRepository(Tarea::class)->find($id);
            if (!is_null($extra) && !is_null($tarea)) {
                $tarea->setExtra($extra);
                $em->persist($tarea);
                $em->flush();
                return $this->handleView($this->view(['status' => 'ok'], Response::HTTP_OK));
            } else {
                return $this->handleView($this->view(['errors' => 'Objeto no encontrado'], Response::HTTP_NOT_FOUND));
            }
        } catch (Exception $e) {
            return $this->handleView($this->view([$e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR));
        }
    }

    /**
     * List an Actividad's tareas.
     * @Rest\Get("/actividad/{id}/tareas")
     *
     * @return Response
     */
    public function getActividadTareasAction(Request $request, $id)
    {
        $repository = $this->getDoctrine()->getRepository(Actividad::class);
        $actividad = $repository->find($id);
        if (!is_null($actividad)) {
            $tareas = $actividad->getTareas();
            return $this->handleView($this->view($tareas));
        } else {
            return $this->handleView($this->view(['errors' => 'Objeto no encontrado'], Response::HTTP_NOT_FOUND));
        }
    }

    /**
     * Lists all Saltos from an Actividad.
     * @Rest\Get("/actividad/{id}/saltos")
     *
     * @return Response
     */
    public function getActividadSaltosAction($id)
    {
        $repository = $this->getDoctrine()->getRepository(Actividad::class);
        $actividad = $repository->find($id);
        if (is_null($actividad)) {
            return $this->handleView($this->view(['errors' => 'Objeto no encontrado'], Response::HTTP_NOT_FOUND));
        }
        $planificacion = $actividad->getPlanificacion();
        if(is_null($planificacion)){
            return $this->handleView($this->view(['errors' => "La actividad no es ".self::BIFURCADA_NAME], Response::HTTP_UNPROCESSABLE_ENTITY));
        }
        $saltos = $planificacion->getSaltos();
        return $this->handleView($this->view($saltos));
    }

    /**
     * Create a planificacion for an Actividad
     * @Rest\Post("/actividad/{id}/planificacion")
     *
     * @return Response
     */
    public function createPlanificacionForActividadAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        try {
            $actividad = $em->getRepository(Actividad::class)->find($id);
            if (!is_null($actividad)) {
                $planificacion = new Planificacion();
                $em->persist($planificacion);
                $em->flush();
                $actividad->setPlanificacion($planificacion);
                $em->persist($actividad);
                $em->flush();
                return $this->handleView($this->view(['status' => 'ok'], Response::HTTP_OK));
            } else {
                return $this->handleView($this->view(['errors' => 'Objeto no encontrado'], Response::HTTP_NOT_FOUND));
            }
        } catch (Exception $e) {
            return $this->handleView($this->view([$e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR));
        }
    }

    /**
     * Create a Salto for an Actividad.
     * @Rest\Post("/actividad/{id}/salto")
     *
     * @return Response
     */
    public function postSaltoForActividadAction(Request $request, $id)
    {
        $salto = new Salto();
        $data = json_decode($request->getContent(), true);

        if (
            !array_key_exists("origen", $data) || !array_key_exists("condicion", $data)
        ) {
            return $this->handleView($this->view(['errors' => 'Faltan campos en el request'], Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        try {
            $em = $this->getDoctrine()->getManager();
            $actividad = $em->getRepository(Actividad::class)->find($id);
            if (is_null($actividad)) {
                return $this->handleView($this->view(['errors' => 'Objeto no encontrado: actividad ' . $id], Response::HTTP_NOT_FOUND));
            }
            $planificacion = $actividad->getPlanificacion();
            if(is_null($planificacion)){
                return $this->handleView($this->view(['errors' => "La actividad no es ".self::BIFURCADA_NAME], Response::HTTP_UNPROCESSABLE_ENTITY));
            }
            $salto->setPlanificacion($planificacion);
            $tareaRepository = $em->getRepository(Tarea::class);
            $origen = $tareaRepository->find($data["origen"]);
            if (is_null($origen)) {
                return $this->handleView($this->view(['errors' => 'Objeto no encontrado: tarea origen ' . $data["origen"]], Response::HTTP_NOT_FOUND));
            }
            $salto->setOrigen($origen);
            if (array_key_exists("destinos", $data) && !is_null($data["destinos"])) {
                foreach ($data["destinos"] as $destino_id) {
                    $tareaDb = $tareaRepository->find($destino_id);
                    if (is_null($tareaDb)) {
                        return $this->handleView($this->view(['errors' => 'Objeto no encontrado: tarea destino ' . $destino_id], Response::HTTP_NOT_FOUND));
                    }
                    $salto->addDestino($tareaDb);
                }
            }
            $salto->setCondicion($data["condicion"]);
            if(array_key_exists("respuesta", $data) && !is_null($data["respuesta"])){
                $salto->setRespuesta($data["respuesta"]);
            }

            $em->persist($salto);
            $em->flush();
            return $this->handleView($this->view($salto, Response::HTTP_CREATED));
        } catch (Exception $e) {
            return $this->handleView($this->view(["errors" => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR));
        }
    }
}
