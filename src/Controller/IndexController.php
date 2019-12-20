<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use App\Entity\Actividad;
use App\Entity\Idioma;
use App\Entity\Planificacion;
use App\Entity\Dominio;
use App\Entity\Tarea;
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
    /**
     * Lists all Idiomas.
     * @Rest\Get("/idioma")
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
     * Lists all Planificacion.
     * @Rest\Get("/planificacion")
     *
     * @return Response
     */
    public function getPlanificacionAction()
    {
        $repository = $this->getDoctrine()->getRepository(Planificacion::class);
        $planificacion = $repository->findall();
        return $this->handleView($this->view($planificacion));
    }

    /**
     * Lists all Dominio.
     * @Rest\Get("/dominio")
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
            $em->persist($dominio);
            $em->flush();
            return $this->handleView($this->view($dominio, Response::HTTP_CREATED));
        }
        return $this->handleView($this->view($form->getErrors()));
    }

    /**
     * Lists all Actividad.
     * @Rest\Get("/actividad")
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
                return $this->handleView($this->view(['errors' => 'Objeto no encotrado'], Response::HTTP_NOT_FOUND));
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
                return $this->handleView($this->view(['errors' => 'Objeto no encotrado'], Response::HTTP_NOT_FOUND));
            }
        } catch (Exception $e) {
            return $this->handleView($this->view([$e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR));
        }
    }

    /**
     * Update planificacion on Actividad.
     * @Rest\Post("/actividad/{id}/planificacion")
     *
     * @return Response
     */
    public function updatePlanificacionOnActividadAction(Request $request, $id)
    {
        $data = json_decode($request->getContent(), true);
        $em = $this->getDoctrine()->getManager();

        try {
            if (!array_key_exists("planificacion", $data)) {
                return $this->handleView($this->view(['errors' => 'Faltan campos en el request'], Response::HTTP_UNPROCESSABLE_ENTITY));
            }
            $planificacion = $em->getRepository(Planificacion::class)->find($data["planificacion"]);
            $actividad = $em->getRepository(Actividad::class)->find($id);
            if (!is_null($planificacion) && !is_null($actividad)) {
                $actividad->setPlanificacion($planificacion);
                $em->persist($actividad);
                $em->flush();
                return $this->handleView($this->view(['status' => 'ok'], Response::HTTP_OK));
            } else {
                return $this->handleView($this->view(['errors' => 'Objeto no encotrado'], Response::HTTP_NOT_FOUND));
            }
        } catch (Exception $e) {
            return $this->handleView($this->view([$e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR));
        }
    }

    /**
     * Lists all Tarea.
     * @Rest\Get("/tarea")
     *
     * @return Response
     */
    public function getTareaAction()
    {
        $repository = $this->getDoctrine()->getRepository(Tarea::class);
        $tareas = $repository->findall();
        return $this->handleView($this->view($tareas));
    }

    /**
     * Lists all TipoTarea.
     * @Rest\Get("/tipo-tarea")
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
     * Lists all TipoTarea.
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
                return $this->handleView($this->view(['errors' => 'Objeto no encotrado'], Response::HTTP_NOT_FOUND));
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
                return $this->handleView($this->view(['errors' => 'Objeto no encotrado'], Response::HTTP_NOT_FOUND));
            }
        } catch (Exception $e) {
            return $this->handleView($this->view([$e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR));
        }
    }
}
