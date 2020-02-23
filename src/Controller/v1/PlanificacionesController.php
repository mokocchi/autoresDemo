<?php

namespace App\Controller\v1;

use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Controller\BaseController;
use App\Entity\Actividad;
use App\Entity\Salto;
use App\Entity\Tarea;
use App\Repository\ActividadRepository;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/planificaciones")
 */
class PlanificacionesController extends BaseController
{

    private function checkActividadFound($id)
    {
        return $this->checkEntityFound(Actividad::class, $id);
    }

    private function checkTareaFound($id)
    {
        return $this->checkEntityFound(Tarea::class, $id);
    }

    private function checkOwnActividad($actividad)
    {
        if ($actividad->getAutor()->getId() !== $this->getUser()->getId()) {
            throw new ApiProblemException(
                new ApiProblem(Response::HTTP_FORBIDDEN, "La actividad no pertenece al usuario actual", "No se puede acceder a la actividad"),
            );
        }
    }

    private function checkOwnTarea($tarea)
    {
        if ($tarea->getAutor()->getId() !== $this->getUser()->getId()) {
            throw new ApiProblemException(
                new ApiProblem(Response::HTTP_FORBIDDEN, "La tarea no pertenece al usuario actual", "No se puede acceder a la tarea"),
            );
        }
    }

    private function checkAccessTarea($tarea)
    {
        if ($tarea->getEstado()->getNombre() == "Privado" && $tarea->getAutor()->getId() !== $this->getUser()->getId()) {
            throw new ApiProblemException(
                new ApiProblem(Response::HTTP_FORBIDDEN, "La tarea es privada o no pertenece al usuario actual", "No se puede acceder a la tarea")
            );
        }
    }

    public function checkTareaBelongsToActividad($actividad, $tarea)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var ActividadRepository $repository */
        $repository = $em->getRepository(Actividad::class);
        if (!$repository->hasTarea($actividad->getId(), $tarea)) {
            throw new ApiProblemException(
                new ApiProblem(
                    Response::HTTP_BAD_REQUEST,
                    sprintf("La tarea %s no pertenece a la actividad %s", $tarea->getCodigo(), $actividad->getCodigo()),
                    "La tarea no pertenece a la actividad"
                )
            );
        };
    }

    public function checkGraphHasExit($tareas, $saltos)
    {
        if(count($saltos) == 0) {
            return true;
        }
        $targetedTareas = [];
        foreach ($tareas as $tarea) {
            $targetedTareas[$tarea->getId()] = false;
        }
        foreach ($saltos as $salto) {
            foreach ($salto["destinos"] as $destino) {
                $targetedTareas[$destino->getId()] = true;
            }
        }
        foreach ($targetedTareas as $target) {
            if(!$target) {
                return true;
            }
        }
        throw new ApiProblemException(
            new ApiProblem(
                Response::HTTP_BAD_REQUEST,
                "El grafo no tiene salida",
                "No se puede guardar la planificación"
            )
        );
    }

    /**
     * Setea la planificacion de una actividad
     * @Rest\Put("/{id}", name="put_planificacion_actividad")
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
     *     response=404,
     *     description="Actividad o tarea no encontrada"
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
     *     name="saltos",
     *     in="body",
     *     type="string",
     *     description="Saltos para agregar a la actividad",
     *     @SWG\Schema(type="array",
     *        @SWG\Items(
     *              type="object",
     *              required={"origen", "condicion", "destinos"},
     *              @SWG\Property(property="origen", type="string", description="Código de la tarea origen"),
     *              @SWG\Property(property="condicion", type="enum", description="Condición del salto"),
     *              @SWG\Property(property="respuesta", type="string", description="Respuesta o tarea que condiciona el salto"),
     *              @SWG\Property(property="destinos", type="array", description="Códigos de las tareas destino", @SWG\Items(type="string"))
     *        )
     *     )
     * )
     * 
     * @SWG\Parameter(
     *     required=true,
     *     name="opcionales",
     *     in="body",
     *     type="string",
     *     description="Id de las tareas opcionales",
     *     @SWG\Schema(type="array",
     *        @SWG\Items(
     *              type="integer"
     *        )
     *     )
     * )
     * 
     * @SWG\Parameter(
     *     required=true,
     *     name="iniciales",
     *     in="body",
     *     type="string",
     *     description="Id de las tareas iniciales",
     *     @SWG\Schema(type="array",
     *        @SWG\Items(
     *              type="integer"
     *        )
     *     )
     * )
     * 
     * @SWG\Tag(name="Actividad")
     * @return Response
     */
    public function putPlanificacion($id, Request $request)
    {
        $data = $this->getJsonData($request);

        $this->checkRequiredParameters(["saltos", "iniciales", "opcionales"], $data);
        $this->checkIsArray($data["saltos"], "saltos");
        $this->checkIsArray($data["iniciales"], "iniciales");
        $this->checkIsArray($data["opcionales"], "opcionales");

        foreach ($data["saltos"] as $saltoArray) {
            $this->checkRequiredParameters(["origen", "condicion", "destinos"], $saltoArray);
            $this->checkIsArray($saltoArray["destinos"], "destinos");
        }

        $em = $this->getDoctrine()->getManager();
        $actividad = $this->checkActividadFound($id);
        $this->checkOwnActividad($actividad);

        $saltos = [];
        $this->checkIsArray($data["saltos"], "saltos");
        foreach ($data["saltos"] as $saltoArray) {
            $origen = $this->checkTareaFound($saltoArray["origen"]);
            $this->checkAccessTarea($origen);
            $destinos = [];
            $this->checkIsArray($saltoArray["destinos"], "destinos");
            foreach ($saltoArray["destinos"] as $destinoId) {
                $destino = $this->checkTareaFound($destinoId);
                $this->checkAccessTarea($destino);
                $this->checkTareaBelongsToActividad($actividad, $destino);
                $destinos[] = $destino;
            }

            $saltos[] = [
                "origen" => $origen,
                "destinos" => $destinos,
                "condicion" => $saltoArray["condicion"],
                "respuesta" => (array_key_exists("respuesta", $saltoArray) && $saltoArray["respuesta"]) ?
                    $saltoArray["respuesta"] :
                    null
            ];
        }
        $this->checkGraphHasExit($actividad->getTareas(), $saltos);
        $iniciales = [];
        foreach ($data["iniciales"] as $inicialId) {
            $inicial = $this->checkTareaFound($inicialId);
            $this->checkAccessTarea($inicial);
            $this->checkTareaBelongsToActividad($actividad, $inicial);
            $iniciales[] = $inicial;
        }
        $opcionales = [];
        foreach ($data["opcionales"] as $opcionalId) {
            $opcional = $this->checkTareaFound($opcionalId);
            $this->checkAccessTarea($opcional);
            $this->checkTareaBelongsToActividad($actividad, $opcional);
            $opcionales[] = $opcional;
        }

        $planificacion = $actividad->getPlanificacion();
        $prevSaltos = $planificacion->getSaltos();
        $em = $this->getDoctrine()->getManager();
        foreach ($prevSaltos as $salto) {
            $em->remove($salto);
        }

        foreach ($saltos as $saltoArray) {
            $salto = new Salto();
            $planificacion->addSalto($salto);
            $salto->setOrigen($saltoArray["origen"]);
            foreach ($saltoArray["destinos"] as $destino) {
                $salto->addDestino($destino);
            }
            $salto->setCondicion($saltoArray["condicion"]);
            $salto->setRespuesta($saltoArray["respuesta"]);
            $em->persist($salto);
        }

        $prevOpcionales = $planificacion->getOpcionales();
        foreach ($prevOpcionales as $opcional) {
            $planificacion->removeOpcional($opcional);
        }
        $prevIniciales = $planificacion->getIniciales();
        foreach ($prevIniciales as $inicial) {
            $planificacion->removeInicial($inicial);
        }

        foreach ($opcionales as $opcional) {
            $planificacion->addOpcional($opcional);
        }
        foreach ($iniciales as $inicial) {
            $planificacion->addInicial($inicial);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($planificacion);

        $em->flush();
        return $this->handleView($this->getViewWithGroups($planificacion, "autor"));
    }

    /**
     * Muestra la planificación de una actividad
     * @Rest\Get("/{id}", name="get_planificacion_actividad")
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
    public function getActividadPlanificacionAction($id)
    {
        $actividad = $this->checkActividadFound($id);
        $planificacion = $actividad->getPlanificacion();
        return $this->handleView($this->getViewWithGroups($planificacion, "autor"));
    }
}
