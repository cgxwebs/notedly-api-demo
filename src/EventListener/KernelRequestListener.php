<?php

namespace App\EventListener;

use App\Controller\JsonRequest;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class KernelRequestListener
{
    use JsonRequest;

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        $this->loadJsonRequest($request);
    }
}
