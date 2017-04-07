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

use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\HttpFoundation\Request;
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
     * @param GetResponseForControllerResultEvent $event
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        try {
            RequestAttributesExtractor::extractAttributes($request);
        } catch (RuntimeException $e) {
            return;
        }

        $user = $event->getControllerResult();
        if (!$user instanceof UserInterface || $request->isMethodSafe(false)) {
            return;
        }

        switch ($request->getMethod()) {
            case Request::METHOD_DELETE:
                $this->userManager->deleteUser($user);
                $event->setControllerResult(null);
                break;
            default:
                $this->userManager->updateUser($user);
                break;
        }
    }
}
