<?php

namespace App\Controller\v1\pub;

use App\Api\ApiProblem;
use App\Controller\BaseController;
use App\Entity\Idioma;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;

/**
 * @Route("/idiomas")
 */
class PublicIdiomasController extends BaseController
{
    /**
     * Lista todos los idiomas
     * @Rest\Get(name="get_idiomas")
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
     * @SWG\Tag(name="Idioma")
     * @return Response
     */
    public function getIdiomaAction()
    {
        $repository = $this->getDoctrine()->getRepository(Idioma::class);
        $idiomas = $repository->findall();
        return $this->handleView($this->getViewWithGroups(["results" => $idiomas], "select"));
    }
}
