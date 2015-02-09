<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\FosUser;

use Dunglas\JsonLdApiBundle\Event\Events;
use Dunglas\JsonLdApiBundle\Event\ObjectEvent;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Bridges between FOSUserBundle and DunglasJsonLdApiBundle.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class EventSubscriber implements EventSubscriberInterface
{
    /**
     * @var UserManagerInterface
     */
    private $userManager;

    public function __construct(UserManagerInterface $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PRE_CREATE => ['persistObject', 1],
            Events::PRE_UPDATE => ['persistObject', 1],
            Events::PRE_DELETE => ['deleteObject', 1],
        ];
    }

    /**
     * Persists the given user object.
     *
     * @param ObjectEvent $event
     */
    public function persistObject(ObjectEvent $event)
    {
        $object = $event->getObject();
        if ($object instanceof UserInterface) {
            $this->userManager->updateUser($object);
            $event->stopPropagation();
        }
    }

    /**
     * Removes the given user object.
     *
     * @param ObjectEvent $event
     */
    public function deleteObject(ObjectEvent $event)
    {
        $object = $event->getObject();
        if ($object instanceof UserInterface) {
            $this->userManager->deleteUser($event->getObject());
            $event->stopPropagation();
        }
    }
}
