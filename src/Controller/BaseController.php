<?php

namespace App\Controller;

use App\ApiProblem;
use App\ApiProblemException;
use Exception;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BaseController extends AbstractFOSRestController
{
    protected $logger;
    protected $serializer;

    public function __construct(LoggerInterface $logger, SerializerInterface $serializer)
    {
        $this->logger = $logger;
        $this->serializer = $serializer;
    }

    protected function getViewWithGroups($object, $group)
    {
        $view = $this->view($object);
        return $this->setGroupToView($view, $group);
    }

    protected function setGroupToView($view, $group)
    {
        $context = new Context();
        $context->addGroup($group);
        $view->setContext($context);
        return $view;
    }

    protected function debug($variable)
    {
        throw new ApiProblemException(
            new ApiProblem(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                "error",
                $this->serializer->serialize($variable, "json")
            )
        );
    }

    protected function getJsonData(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        if (is_null($data)) {
            throw new ApiProblemException(
                new ApiProblem(Response::HTTP_BAD_REQUEST, "No hay campos en el json", "Hubo un problema con la petición")
            );
        }
        return $data;
    }
}
