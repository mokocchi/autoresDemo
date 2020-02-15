<?php

namespace App\Controller\v1\pub;

use App\Entity\Actividad;
use App\Entity\Estado;
use Exception;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use FOS\RestBundle\Context\Context;
use Swagger\Annotations as SWG;

/**
 * @Route("/actividades")
 */
class PublicActividadesController extends AbstractFOSRestController
{
    const BIFURCADA_NAME = "Bifurcada";

    /**
     * Lists all Actividad.
     * @Rest\Get
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
     * @SWG\Tag(name="Actividad")
     * @return Response
     */
    public function getActividadesAction()
    {
        try {
            $repository = $this->getDoctrine()->getRepository(Actividad::class);
            $estadoRepository = $this->getDoctrine()->getRepository(Estado::class);
            $estado = $estadoRepository->findOneBy(["nombre" => "Público"]);
            $actividades = $repository->findBy(["estado" => $estado]);

            $view = $this->view($actividades);
            $context = new Context();
            $context->addGroup('publico');
            $view->setContext($context);
            return $this->handleView($view);
        } catch (Exception $e) {
            return $this->handleView($this->view(["errors" => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR));
        }
    }


    /**
     * Shows an Actividad.
     * @Rest\Get("/{id}")
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
     * @SWG\Tag(name="Actividad")
     * @return Response
     */
    public function showActividadAction($id)
    {
        try {
            $repository = $this->getDoctrine()->getRepository(Actividad::class);
            $actividad = $repository->find($id);
            if (is_null($actividad)) {
                return $this->handleView($this->view(['errors' => 'Objeto no encontrado'], Response::HTTP_NOT_FOUND));
            }
            if ($actividad->getEstado()->getNombre() == "Privado") {
                return $this->handleView($this->view(['errors' => 'La actividad es privada'], Response::HTTP_UNAUTHORIZED));
            }

            $view = $this->view($actividad);
            $context = new Context();
            $context->addGroup('publico');
            $view->setContext($context);
            return $this->handleView($view);
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
        foreach ($actividad->getTareas() as $tarea) {
            $jumps[$tarea->getId()] = [];
        }
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
        $iniciales = $planificacion->getIniciales()->map(function ($elem) {
            return $elem->getId();
        })->toArray();
        $opcionales = $planificacion->getOpcionales()->map(function ($elem) {
            return $elem->getId();
        })->toArray();

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
