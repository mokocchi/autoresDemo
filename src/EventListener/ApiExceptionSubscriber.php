<?php

namespace App\EventListener;

use App\ApiProblem;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    private $logger;
    private $serializer;

    public function __construct(LoggerInterface $logger, SerializerInterface $serializer)
    {
        $this->logger = $logger;
        $this->serializer = $serializer;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $e = $event->getThrowable();
        $this->logger->error($e->getMessage());

        if ($e instanceof HttpException) {
            if ($e instanceof NotFoundHttpException) {
                $devMessage = "Recurso no encontrado";
                $usrMessage = "Recuro no encontrado";
            } elseif ($e instanceof AccessDeniedHttpException) {
                $devMessage = "No tenés los permisos suficientes para acceder al recurso";
                $usrMessage = "Acceso denegado";
            } elseif ($e instanceof BadRequestHttpException) {
                $devMessage = "Hubo un problema con el request";
                $usrMessage = "Datos inválidos";
            } elseif ($e instanceof MethodNotAllowedHttpException) {
                $devMessage = "Método no permitido";
                $usrMessage = "Ocurrió un error";
            } else {
                $devMessage = "Ocurrió un error";
                $usrMessage = "Ocurrió un error";
            }
            $apiProblem = new ApiProblem(
                $e->getStatusCode(),
                $devMessage,
                $usrMessage
            );
            $response = new JsonResponse(
                json_decode($this->serializer->serialize($apiProblem, "json")),
                $e->getStatusCode()
            );
        } else {
            $apiProblem = new ApiProblem(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                "Error interno del servidor",
                "Ocurrió un error"
            );
            $response = new JsonResponse(
                json_decode($this->serializer->serialize($apiProblem, "json")),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
        // $response = new JsonResponse("hola");
        $event->setResponse($response);
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::EXCEPTION => 'onKernelException'
        );
    }
}
