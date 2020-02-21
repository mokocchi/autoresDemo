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
                $this->serializer->serialize($variable?:"null", "json"),
                "error"
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

    protected function checkPropertyNotUsed($class, $property, $value, $message)
    {
        $entityDb = $this->getDoctrine()->getRepository($class)->findOneBy([$property => $value]);

        if (!is_null($entityDb)) {
            throw new ApiProblemException(
                new ApiProblem(Response::HTTP_BAD_REQUEST, $message, $message)
            );
        }
    }

    protected function checkRequiredParameters(array $parameters, array $data)
    {
        foreach ($parameters as $parameter) {
            if (!array_key_exists($parameter, $data) || is_null($data[$parameter])) {
                throw new ApiProblemException(
                    new ApiProblem(Response::HTTP_BAD_REQUEST, "Uno o más de los campos requeridos falta o es nulo", "Faltan datos")
                );
            }
        }
    }

    protected function checkEntityFound($class, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository($class)->find($id);
        if (is_null($entity)) {
            $path = explode('\\', $class);
            $class = array_pop($path);
            throw new ApiProblemException(
                new ApiProblem(Response::HTTP_NOT_FOUND, sprintf("No se encontró: %s", $class), sprintf("No se encontró: %s", $class))
            );
        }
        return $entity;
    }

    protected function checkFormValidity($form)
    {
        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->logger->alert("Datos inválidos: " . json_decode($form->getErrors()));
            throw new ApiProblemException(
                new ApiProblem(Response::HTTP_BAD_REQUEST, "Se recibieron datos inválidos", "Datos inválidos"),
            );
        }
    }
}
