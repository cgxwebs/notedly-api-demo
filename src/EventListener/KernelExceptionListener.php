<?php

namespace App\EventListener;

use App\Controller\ApiHelper;
use App\Exception\ApiException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;

class KernelExceptionListener
{
    use ApiHelper;

    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        if ($exception instanceof ApiException) {
            $content = $this->errorResponse(
                $exception->getMessage(),
                $exception->getContext()
            );
        } elseif ($exception instanceof NotFoundHttpException ||
            $exception instanceof AccessDeniedHttpException ||
            $exception instanceof MethodNotAllowedHttpException
        ) {
            $content = $this->errorResponse(
                $exception->getMessage()
            );
        } else {
            return;
        }

        $jsonResponse = new JsonResponse(
            $this->serializer->serialize($content, 'json'),
            $exception->getStatusCode(), [], true
        );

        $event->setResponse($jsonResponse);
    }
}
