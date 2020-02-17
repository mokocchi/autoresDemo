<?php

namespace App\Controller\v1\pub;

use App\ApiProblem;
use App\Controller\BaseController;
use App\Entity\Actividad;
use App\Entity\Estado;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;

/**
 * @Route("/actividades")
 */
class PublicActividadesController extends BaseController
{
    /**
     * Lista todas las actividades públicas
     * @Rest\Get(name="get_actividades_public")
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

            return $this->handleView($this->getViewWithGroups(["results" => $actividades], "publico"));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->handleView($this->view(
                new ApiProblem(Response::HTTP_INTERNAL_SERVER_ERROR, "Error interno del servidor", "Ocurrió un error"),
                Response::HTTP_INTERNAL_SERVER_ERROR
            ));
        }
    }


    /**
     * Muestra una actividad pública
     * @Rest\Get("/{id}", name="show_actividad_public")
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
            if ($actividad->getEstado()->getNombre() == "Privado") {
                return $this->handleView($this->view(
                    new ApiProblem(Response::HTTP_FORBIDDEN, "La actividad es privada", "No se puede acceder a la actividad"),
                    Response::HTTP_FORBIDDEN
                ));
            }

            return $this->handleView($this->getViewWithGroups($actividad, "publico"));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->handleView($this->view(
                new ApiProblem(Response::HTTP_INTERNAL_SERVER_ERROR, "Error interno del servidor", "Ocurrió un error"),
                Response::HTTP_INTERNAL_SERVER_ERROR
            ));
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
        try {
            $repository = $this->getDoctrine()->getRepository(Actividad::class);
            $actividad = $repository->find($id);
            if (is_null($actividad)) {
                return $this->handleView($this->view(
                    new ApiProblem(Response::HTTP_NOT_FOUND, "El id no corresponde a ninguna tarea", "No se encontró la tarea"),
                    Response::HTTP_NOT_FOUND
                ));
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
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->handleView($this->view(
                new ApiProblem(Response::HTTP_INTERNAL_SERVER_ERROR, "Error interno del servidor", "Ocurrió un error"),
                Response::HTTP_INTERNAL_SERVER_ERROR
            ));
        }
    }
}
