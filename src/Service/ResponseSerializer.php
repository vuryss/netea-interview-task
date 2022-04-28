<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

#[AsEventListener(event: KernelEvents::VIEW, method: 'onKernelView')]
class ResponseSerializer
{
    public function __construct(
        private readonly SerializerInterface $serializer
    ) {
    }

    public function onKernelView(ViewEvent $event): void
    {
        $controllerResult = $event->getControllerResult();
        $existingResponse = $event->getResponse();

        if ($controllerResult instanceof Response || $existingResponse !== null) {
            return;
        }

        $response = new JsonResponse(
            $this->serializer->serialize($controllerResult, 'json'),
            json: true
        );

        $event->setResponse($response);
    }
}
