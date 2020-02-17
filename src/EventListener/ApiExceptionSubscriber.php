<?php

namespace App\EventListener;

use App\ApiProblem;
use JMS\Serializer\SerializerInterface;
use OAuth2\OAuth2;
use OAuth2\OAuth2AuthenticateException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
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
            } elseif ($e instanceof UnauthorizedHttpException) {
                $devMessage = "No se recibió información de autorización";
                $usrMessage = "No autorizado";
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
        $event->setResponse($response);
    }

    public function onKernelResponse(ResponseEvent $e)
    {
        /** @var Response $response */
        $response = $e->getResponse();
        $data = json_decode($response->getContent(), true);
        if ($data) {
            if (array_key_exists("error", $data)) {
                $this->logger->error(implode(",", $data));
                if ($data["error"] == OAuth2::ERROR_INVALID_GRANT) {
                    $devMessage = "El token expiró o es inválido";
                    $usrMessage = "Ocurrió un error de autenticación";
                } else {
                    $devMessage = "Error desconocido";
                    $usrMessage = "Ocurrió un error";
                }

                $apiProblem = new ApiProblem($response->getStatusCode(), $devMessage, $usrMessage);
                $response = new JsonResponse(
                    json_decode($this->serializer->serialize($apiProblem, "json")),
                    $response->getStatusCode()
                );
                $e->setResponse($response);
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::EXCEPTION => 'onKernelException',
            KernelEvents::RESPONSE => 'onKernelResponse'
        );
    }
}
