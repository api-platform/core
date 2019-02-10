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

namespace ApiPlatform\Core\Bridge\FosUser;

use ApiPlatform\Core\Event\EventInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

/**
 * Bridges between FOSUserBundle and API Platform Core.
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
     * @deprecated since version 2.5, to be removed in 3.0
     */
    public function onKernelView(GetResponseForControllerResultEvent $event): void
    {
        @trigger_error(sprintf('The method %s() is deprecated since 2.5 and will be removed in 3.0.', __METHOD__), E_USER_DEPRECATED);

        $this->handleEvent($event);
    }

    /**
     * Persists, updates or delete data return by the controller if applicable.
     */
    public function handleEvent(/*EventInterface */$event): void
    {
        if ($event instanceof EventInterface) {
            $request = $event->getContext()['request'];
        } elseif ($event instanceof GetResponseForControllerResultEvent) {
            @trigger_error(sprintf('Passing an instance of "%s" as argument of "%s" is deprecated since 2.5 and will not be possible anymore in 3.0. Pass an instance of "%s" instead.', GetResponseForControllerResultEvent::class, __METHOD__, EventInterface::class), E_USER_DEPRECATED);

            $request = $event->getRequest();
        } else {
            return;
        }

        if (!RequestAttributesExtractor::extractAttributes($request)) {
            return;
        }

        if ($event instanceof EventInterface) {
            $user = $event->getData();
        } elseif ($event instanceof GetResponseForControllerResultEvent) {
            $user = $event->getControllerResult();
        } else {
            return;
        }
        if (!$user instanceof UserInterface || $request->isMethodSafe(false)) {
            return;
        }

        if ('DELETE' === $request->getMethod()) {
            $this->userManager->deleteUser($user);
            if ($event instanceof EventInterface) {
                $event->setData(null);
            } elseif ($event instanceof GetResponseForControllerResultEvent) {
                $event->setControllerResult(null);
            }
        } else {
            $this->userManager->updateUser($user);
        }
    }
}
