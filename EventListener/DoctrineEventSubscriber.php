<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Dunglas\JsonLdApiBundle\DunglasJsonLdApiEvents;
use Dunglas\JsonLdApiBundle\Event\ObjectEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Doctrine Event Listener.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DoctrineEventSubscriber implements EventSubscriberInterface
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
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            DunglasJsonLdApiEvents::PRE_CREATE => ['persistObject', 0],
            DunglasJsonLdApiEvents::PRE_UPDATE => ['persistObject', 0],
            DunglasJsonLdApiEvents::PRE_DELETE => ['deleteObject', 0],
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
    }
}
