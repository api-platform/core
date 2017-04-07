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

namespace ApiPlatform\Core\Bridge\Doctrine\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

/**
 * Bridges Doctrine and the API system.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class WriteListener
{
    private $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * Persists, updates or delete data return by the controller if applicable.
     *
     * @param GetResponseForControllerResultEvent $event
     *
     * @return mixed
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        if ($request->isMethodSafe(false)) {
            return;
        }

        $resourceClass = $request->attributes->get('_api_resource_class');
        if (null === $resourceClass) {
            return;
        }

        $controllerResult = $event->getControllerResult();
        if (null === $objectManager = $this->getManager($resourceClass, $controllerResult)) {
            return;
        }

        switch ($request->getMethod()) {
            case Request::METHOD_POST:
                $objectManager->persist($controllerResult);
                break;
            case Request::METHOD_DELETE:
                $objectManager->remove($controllerResult);
                $event->setControllerResult(null);
                break;
        }

        $objectManager->flush();
    }

    /**
     * Gets the manager if applicable.
     *
     * @param string $resourceClass
     * @param mixed  $data
     *
     * @return ObjectManager|null
     */
    private function getManager(string $resourceClass, $data)
    {
        $objectManager = $this->managerRegistry->getManagerForClass($resourceClass);

        if (null === $objectManager || !is_object($data)) {
            return;
        }

        return $objectManager;
    }
}
