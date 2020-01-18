<?php

namespace App\Controller\v1;

use App\Entity\Actividad;
use App\Entity\Planificacion;
use App\Entity\Salto;
use App\Entity\Tarea;
use App\Form\ActividadType;
use Exception;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/actividades")
 */
class ActividadesController extends AbstractFOSRestController
{
    const BIFURCADA_NAME = "Bifurcada";

    /**
     * Lists all Actividad.
     * @Rest\Get
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
     * @Rest\Get("/{id}")
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
     * @Rest\Post
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
                $planificacion = new Planificacion();
                $em->persist($planificacion);
                $em->flush();
                $actividad->setPlanificacion($planificacion);
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
     * Add a Tarea to an Activity.
     * @Rest\Post("/{id}/tareas")
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
     * List an Actividad's tareas.
     * @Rest\Get("/{id}/tareas")
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
     * @Rest\Get("/{id}/saltos")
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
        $saltos = $planificacion->getSaltos();
        return $this->handleView($this->view($saltos));
    }

    /**
     * Create a Salto for an Actividad.
     * @Rest\Post("/{id}/saltos")
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
                return $this->handleView($this->view(['errors' => 'Objeto no encontrado:actividad'], Response::HTTP_NOT_FOUND));
            }
            $planificacion = $actividad->getPlanificacion();
            $salto->setPlanificacion($planificacion);
            $tareaRepository = $em->getRepository(Tarea::class);
            $origen = $tareaRepository->find($data["origen"]);
            if (is_null($origen)) {
                return $this->handleView($this->view(['errors' => 'Objeto no encontrado: tarea origen'], Response::HTTP_NOT_FOUND));
            }
            $salto->setOrigen($origen);
            if (array_key_exists("destinos", $data) && !is_null($data["destinos"])) {
                foreach ($data["destinos"] as $destino_id) {
                    $tareaDb = $tareaRepository->find($destino_id);
                    if (is_null($tareaDb)) {
                        return $this->handleView($this->view(['errors' => 'Objeto no encontrado: tarea destino'], Response::HTTP_NOT_FOUND));
                    }
                    $salto->addDestino($tareaDb);
                }
            }
            $salto->setCondicion($data["condicion"]);
            if (array_key_exists("respuesta", $data) && !is_null($data["respuesta"])) {
                $salto->setRespuesta($data["respuesta"]);
            }

            $em->persist($salto);
            $em->flush();
            return $this->handleView($this->view($salto, Response::HTTP_CREATED));
        } catch (Exception $e) {
            return $this->handleView($this->view(["errors" => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR));
        }
    }

    /**
     * Deletes all saltos from an Actividad
     * @Rest\Delete("/{id}/saltos")
     * @return Response
     */
    public function deleteSaltosAction($id)
    {
        try {
            $repository = $this->getDoctrine()->getRepository(Actividad::class);
            $actividad = $repository->find($id);
            if (is_null($actividad)) {
                return $this->handleView($this->view(['errors' => 'Objeto no encontrado: actividad'], Response::HTTP_NOT_FOUND));
            }
            $planificacion = $actividad->getPlanificacion();
            $saltos = $planificacion->getSaltos();
            $em = $this->getDoctrine()->getManager();
            foreach ($saltos as $salto) {
                $em->remove($salto);
            }
            $em->persist($planificacion);
            $em->flush();
        } catch (Exception $e) {
            return $this->handleView($this->view(["errors" => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR));
        }
    }

    /**
     * Create a Salto for an Actividad.
     * @Rest\Post("/{id}/planificaciones")
     *
     * @return Response
     */
    public function updatePlanificacionSettings(Request $request, $id)
    {
        $data = json_decode($request->getContent(), true);

        if (
            !array_key_exists("iniciales", $data) || !array_key_exists("opcionales", $data)
        ) {
            return $this->handleView($this->view(['errors' => 'Faltan campos en el request'], Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        try {
            $repository = $this->getDoctrine()->getRepository(Actividad::class);
            $actividad = $repository->find($id);
            if (is_null($actividad)) {
                return $this->handleView($this->view(['errors' => 'Objeto no encontrado: actividad'], Response::HTTP_NOT_FOUND));
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
                    return $this->handleView($this->view(['errors' => 'Objeto no encontrado: tarea inicial'], Response::HTTP_NOT_FOUND));
                }
                $planificacion->addInicial($tareaInicial);
            }
            $opcionales = $data["opcionales"];
            foreach ($opcionales as $opcional) {
                $tareaOpcional = $tareaRepository->find($opcional);
                if (is_null($tareaOpcional)) {
                    return $this->handleView($this->view(['errors' => 'Objeto no encontrado: tarea opcional'], Response::HTTP_NOT_FOUND));
                }
                $planificacion->addOpcional($tareaOpcional);
            }
            $em = $this->getDoctrine()->getManager();
            $em->persist($planificacion);
            $em->flush();
        } catch (Exception $e) {
            return $this->handleView($this->view(["errors" => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR));
        }
    }



    /**
     * Download the JSON definition for the Actividad.
     * @Rest\Get("/{id}/download")
     *
     * @return Response
     */
    public function downloadActividadAction($id)
    {
        $repository = $this->getDoctrine()->getRepository(Actividad::class);
        $actividad = $repository->find($id);
        if (is_null($actividad)) {
            return $this->handleView($this->view(['errors' => 'Objeto no encontrado'], Response::HTTP_NOT_FOUND));
        }
        $JSON = [];
        $JSON["language"] = $actividad->getIdioma()->getCode();
        $educationalActivity = [
            "name" => $actividad->getNombre(),
            "goal" => $actividad->getObjetivo(),
            "sequential" => ($actividad->getTipoPlanificacion()->getNombre() != "Libre")
        ];
        $JSON["educationalActivity"] = $educationalActivity;
        $planificacion = $actividad->getPlanificacion();
        $jumps = [];
        $saltos = $planificacion->getSaltos();
        foreach ($saltos as $salto) {
            $jump = [
                "on" => $salto->getCondicion(),
                "to" => count($salto->getDestinoCodes()) == 0 ?  ["END"] : $salto->getDestinoCodes(),
                "answer" => $salto->getRespuesta()
            ];
            //multiple jumps for each tarea
            $jumps[$salto->getOrigen()->getId()][] = $jump;
        }
        $iniciales = $planificacion->getIniciales()->map(function($elem){return $elem->getId();})->toArray();
        $opcionales = $planificacion->getOpcionales()->map(function($elem){return $elem->getId();})->toArray();

        $tasks = [];
        foreach ($actividad->getTareas() as $tarea) {
            $task = [
                "code" => $tarea->getCodigo(),
                "name" => $tarea->getNombre(),
                "instruction" => $tarea->getConsigna(),
                "initial" => in_array($tarea->getId(), $iniciales),
                "optional" => in_array($tarea->getId(), $opcionales), 
                "type" => $tarea->getTipo()->getCodigo(),
                "jumps" => count($jumps) == 0 ? [] : $jumps[$tarea->getId()]
            ];
            foreach ($tarea->getExtra() as $key => $value) {
                $task[$key] = $value;
            }
            $tasks[] = $task;
        }
        $JSON["tasks"] = $tasks;
        //return $this->handleView($this->view($JSON));


        $fileContent = json_encode($JSON, JSON_PRETTY_PRINT);
        $response = new Response($fileContent);

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            iconv("UTF-8", "ASCII//TRANSLIT", $actividad->getNombre()) . '.json'
        );

        $response->headers->set('Content-Disposition', $disposition);
        return $response;
    }
}
