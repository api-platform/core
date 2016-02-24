<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\FosUser;

use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

/**
 * Bridges between FOSUserBundle and ApiPlatformBundle.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
final class EventListener
{
    private $userManager;

    public function __construct(UserManagerInterface $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * Persists, updates or delete data return by the controller if applicable.
     *
     * @param GetResponseForControllerResultEvent $event
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $user = $event->getControllerResult();
        if (!$user instanceof UserInterface) {
            return;
        }

        switch ($event->getRequest()->getMethod()) {
            case Request::METHOD_POST:
            case Request::METHOD_PUT:
                $this->userManager->updateUser($user, false);
                break;

            case Request::METHOD_DELETE:
                $this->userManager->deleteUser($user);
                $event->setControllerResult(null);
                break;
        }
    }
}
