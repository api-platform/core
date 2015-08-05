<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Doctrine\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ManagerRegistry;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

/**
 * Bridges Doctrine and the API system.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ManagerViewListener
{
    /**
     * @var ManagerRegistry
     */
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
        if (!in_array($request->getMethod(), [Request::METHOD_POST, Request::METHOD_PUT, Request::METHOD_DELETE])) {
            return;
        }

        $resourceType = $request->attributes->get('_resource_type');
        if (!$resourceType) {
            return;
        }

        $controllerResult = $event->getControllerResult();

        if (null === $objectManager = $this->getManager($resourceType, $controllerResult)) {
            return $controllerResult;
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
     * @param ResourceInterface $resourceType
     * @param mixed             $data
     *
     * @return ObjectManager|null
     */
    private function getManager(ResourceInterface $resourceType, $data)
    {
        $objectManager = $this->managerRegistry->getManagerForClass($resourceType->getEntityClass());

        if (null === $objectManager || !is_object($data)) {
            return;
        }

        return $objectManager;
    }
}
