<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Doctrine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Dunglas\ApiBundle\Event\DataEvent;
use Dunglas\ApiBundle\Event\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Bridges between Doctrine and DunglasApiBundle.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class EventSubscriber implements EventSubscriberInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    public function __construct(EventDispatcherInterface $eventDispatcher, ManagerRegistry $managerRegistry)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PRE_CREATE => ['persistObject', 0],
            Events::PRE_UPDATE => ['updateObject', 0],
            Events::PRE_DELETE => ['deleteObject', 0],
        ];
    }

    /**
     * Persists the given object and flushes.
     *
     * @param DataEvent $event
     */
    public function persistObject(DataEvent $event)
    {
        if (!$objectManager = $this->getManagerIfApplicable($event)) {
            return;
        }

        $objectManager->persist($event->getData());
        $objectManager->flush();

        $this->eventDispatcher->dispatch(Events::POST_CREATE, $event);
        $event->stopPropagation();
    }

    /**
     * Updates the given object and flushes.
     *
     * @param DataEvent $event
     */
    public function updateObject(DataEvent $event)
    {
        if (!$objectManager = $this->getManagerIfApplicable($event)) {
            return;
        }

        $objectManager->flush();

        $this->eventDispatcher->dispatch(Events::POST_UPDATE, $event);
        $event->stopPropagation();
    }

    /**
     * Removes the given object and flushes.
     *
     * @param DataEvent $event
     */
    public function deleteObject(DataEvent $event)
    {
        if (!$objectManager = $this->getManagerIfApplicable($event)) {
            return;
        }

        $objectManager->remove($event->getData());
        $objectManager->flush();

        $this->eventDispatcher->dispatch(Events::POST_DELETE, $event);
        $event->stopPropagation();
    }

    /**
     * Gets the manager if applicable.
     *
     * @param DataEvent $event
     *
     * @return ObjectManager|false
     */
    private function getManagerIfApplicable(DataEvent $event)
    {
        $objectManager = $this->managerRegistry->getManagerForClass($event->getResource()->getEntityClass());
        $object = $event->getData();

        if (null !== $objectManager && is_object($object)) {
            return $objectManager;
        }

        return false;
    }
}
