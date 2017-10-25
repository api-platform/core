<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\EventListener;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

/**
 * Bridges persistense and the API system.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class WriteListener
{
    private $dataPersister;

    public function __construct(DataPersisterInterface $dataPersister)
    {
        $this->dataPersister = $dataPersister;
    }

    /**
     * Persists, updates or delete data return by the controller if applicable.
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        if ($request->isMethodSafe(false) || !$request->attributes->has('_api_resource_class')) {
            return;
        }

        $controllerResult = $event->getControllerResult();
        if (!$this->dataPersister->supports($controllerResult)) {
            return;
        }

        switch ($request->getMethod()) {
            case Request::METHOD_PUT:
            case Request::METHOD_PATCH:
            case Request::METHOD_POST:
                $this->dataPersister->persist($controllerResult);
                break;
            case Request::METHOD_DELETE:
                $this->dataPersister->remove($controllerResult);
                $event->setControllerResult(null);
                break;
        }
    }
}
