<?php

namespace App\Controller\v1;

use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Controller\BaseController;
use App\Entity\Plano;
use App\Entity\Tarea;
use App\Form\TareaType;
use App\Pagination\PaginationFactory;
use App\Security\Voter\TareaVoter;
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
    private const TIPOS_EXTRA = ["select", "multiple", "counters", "collect"];

    public function checkCodigoNotUsed($codigo)
    {
        $this->checkPropertyNotUsed(Tarea::class, "codigo", $codigo, "Ya existe una tarea con el mismo código");
    }

    private function checkTareaFound($id)
    {
        return $this->checkEntityFound(Tarea::class, $id);
    }

    public function checkExtraValidity(array $extra, Tarea $tarea)
    {
        if (!array_key_exists("elements", $extra)) {
            throw new ApiProblemException(
                new ApiProblem(
                    Response::HTTP_BAD_REQUEST,
                    "Falta el campo elements en el extraData",
                    "Faltan elementos"
                )
            );
        }

        if ("counters" == $tarea->getTipo()->getCodigo()) {
            if (!array_key_exists("byScore", $extra)) {
                throw new ApiProblemException(
                    new ApiProblem(
                        Response::HTTP_BAD_REQUEST,
                        "Falta el campo byScore en el extraData",
                        "Faltan criterios"
                    )
                );
            }
            foreach ($extra["byScore"] as $criterio) {
                if (!array_key_exists("scores", $criterio)) {
                    throw new ApiProblemException(
                        new ApiProblem(
                            Response::HTTP_BAD_REQUEST,
                            "Falta el campo scores en byScore",
                            "Faltan puntajes"
                        )
                    );
                }
                if (!array_key_exists("name", $criterio)) {
                    throw new ApiProblemException(
                        new ApiProblem(
                            Response::HTTP_BAD_REQUEST,
                            "El criterio no tiene nombre",
                            "Falta nombre del criterio"
                        )
                    );
                }
                if (!array_key_exists("message", $criterio)) {
                    throw new ApiProblemException(
                        new ApiProblem(
                            Response::HTTP_BAD_REQUEST,
                            "Falta el campo message para criterio " . $criterio["name"],
                            "Falta mensaje del criterio " . $criterio["name"]
                        )
                    );
                }
                $settedElements = array_keys($criterio["scores"]);
                if (count($settedElements) < count($extra["elements"])) {
                    throw new ApiProblemException(
                        new ApiProblem(
                            Response::HTTP_BAD_REQUEST,
                            "Falta llenar valores en el criterio " . $criterio["name"],
                            "Faltan criterios"
                        )
                    );
                }
            }
        }

        if ("collect" == $tarea->getTipo()->getCodigo()) {
            foreach ($extra["elements"] as $element) {
                if (!array_key_exists("deposits", $element) || count($element["deposits"]) == 0) {
                    throw new ApiProblemException(
                        new ApiProblem(
                            Response::HTTP_BAD_REQUEST,
                            "Falta el campo deposits en el elemento " . $element["nombre"],
                            "Faltan depósitos en el elemento " . $element["nombre"]
                        )
                    );
                }
            }
        }
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
    public function getActividadForUserAction(Request $request, PaginationFactory $paginationFactory)
    {
        $nombre = $request->query->get('nombre');
        /** @var TareaRepository $repository */
        $repository = $this->getDoctrine()->getRepository(Tarea::class);
        $qb = $repository->findAllUserQueryBuilder($nombre, $this->getUser());
        $paginatedCollection = $paginationFactory->createCollection($qb, $request, 'get_tareas_user');
        return $this->handleView($this->getViewWithGroups($paginatedCollection, "autor"));
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
     * @SWG\Parameter(
     *     name="extraData",
     *     in="body",
     *     type="array",
     *     description="Id del estado de la tarea",
     *     @SWG\Schema(type="array",
     *        @SWG\Items(
     *              type="object",
     *              required={"elements"},
     *              @SWG\Property(property="elements", type="array", description="Elementos de la tarea", 
     *                  @SWG\Items(
     *                      type="object",
     *                      required={"code", "name"},
     *                      @SWG\Property(property="code", type="string", description="Código del elemento"),
     *                      @SWG\Property(property="name", type="string", description="Nombre del elemento")
     *                  )
     *              ),
     *              @SWG\Property(property="validElements", type="array", description="Elementos válidos de la tarea", 
     *                  @SWG\Items(type="string")
     *              ),
     *              @SWG\Property(property="byScore", type="array", description="Criterios de contadores de la tarea", 
     *                  @SWG\Items(
     *                      type="object",
     *                      required={"name", "message", "scores"},
     *                      @SWG\Property(property="name", type="string", description="Nombre del criterio"),
     *                      @SWG\Property(property="message", type="string", description="Mensaje del criterio"),
     *                      @SWG\Property(property="scores", type="array", description="Criterios de contadores de la tarea", 
     *                          @SWG\Items(type="object", @SWG\Property(property="[codigo]", type="string", description="Valor del contador para el elemento")
     *                      )
     *                  )
     *              )
     *           )
     *        )
     *     )
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
        if (in_array($tarea->getTipo()->getCodigo(), self::TIPOS_EXTRA)) {
            if (!array_key_exists("extraData", $data)) {
                throw new ApiProblemException(
                    new ApiProblem(
                        Response::HTTP_BAD_REQUEST,
                        "Falta el campo extraData en el request",
                        "Faltan datos"
                    )
                );
            }
            $this->checkExtraValidity($data["extraData"], $tarea);
        }
        $tarea->setExtra($data["extraData"]);
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
        $this->denyAccessUnlessGranted(TareaVoter::ACCESS, $tarea);
        return $this->handleView($this->getViewWithGroups($tarea, "autor"));
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
        $tarea = $this->checkTareaFound($id);
        $this->denyAccessUnlessGranted(TareaVoter::OWN, $tarea);

        $uploaderHelper->uploadPlano($uploadedFile, $tarea->getCodigo(), false);
        return $this->handleView($this->view(['status' => 'ok'], Response::HTTP_OK));
    }
}
