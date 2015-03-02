<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\Doctrine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Dunglas\JsonLdApiBundle\Event\Events;
use Dunglas\JsonLdApiBundle\Event\ObjectEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Bridges between Doctrine and DunglasJsonLdApiBundle.
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
     * @param ObjectEvent $event
     */
    public function persistObject(ObjectEvent $event)
    {
        $objectManager = $this->managerRegistry->getManagerForClass($event->getResource()->getEntityClass());
        $objectManager->persist($event->getObject());
        $objectManager->flush();

        $this->eventDispatcher->dispatch(Events::POST_CREATE, $event);
        $event->stopPropagation();
    }

    /**
     * Updates the given object and flushes.
     *
     * @param ObjectEvent $event
     */
    public function updateObject(ObjectEvent $event)
    {
        $objectManager = $this->managerRegistry->getManagerForClass($event->getResource()->getEntityClass());
        $objectManager->flush();

        $this->eventDispatcher->dispatch(Events::POST_UPDATE, $event);
        $event->stopPropagation();
    }

    /**
     * Removes the given object and flushes.
     *
     * @param ObjectEvent $event
     */
    public function deleteObject(ObjectEvent $event)
    {
        $objectManager = $this->managerRegistry->getManagerForClass($event->getResource()->getEntityClass());

        $objectManager->remove($event->getObject());
        $objectManager->flush();

        $this->eventDispatcher->dispatch(Events::POST_DELETE, $event);
        $event->stopPropagation();
    }
}
