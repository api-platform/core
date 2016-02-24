<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\FosUser;

use Dunglas\ApiBundle\Event\DataEvent;
use Dunglas\ApiBundle\Event\Events;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Bridges between FOSUserBundle and DunglasApiBundle.
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
     * @var UserManagerInterface
     */
    private $userManager;

    public function __construct(EventDispatcherInterface $eventDispatcher, UserManagerInterface $userManager)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->userManager = $userManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PRE_CREATE => ['persistObject', 1],
            Events::PRE_UPDATE => ['updateObject', 1],
            Events::PRE_DELETE => ['deleteObject', 1],
        ];
    }

    /**
     * Persists the given user object.
     *
     * @param DataEvent $event
     */
    public function persistObject(DataEvent $event)
    {
        $object = $event->getData();
        if ($object instanceof UserInterface) {
            $this->userManager->updateUser($object);

            $this->eventDispatcher->dispatch(Events::POST_CREATE, $event);
            $event->stopPropagation();
        }
    }

    /**
     * Updates the given user object.
     *
     * @param DataEvent $event
     */
    public function updateObject(DataEvent $event)
    {
        $object = $event->getData();
        if ($object instanceof UserInterface) {
            $this->userManager->updateUser($object);

            $this->eventDispatcher->dispatch(Events::POST_UPDATE, $event);
            $event->stopPropagation();
        }
    }

    /**
     * Removes the given user object.
     *
     * @param DataEvent $event
     */
    public function deleteObject(DataEvent $event)
    {
        $object = $event->getData();
        if ($object instanceof UserInterface) {
            $this->userManager->deleteUser($object);

            $this->eventDispatcher->dispatch(Events::POST_DELETE, $event);
            $event->stopPropagation();
        }
    }
}
