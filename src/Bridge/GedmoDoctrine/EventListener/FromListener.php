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

namespace ApiPlatform\Core\Bridge\GedmoDoctrine\EventListener;

use Gedmo\Blameable\BlameableListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use ApiPlatform\Core\Bridge\GedmoDoctrine\Model\FromUser;

/**
 * Set the "From" header email address as a Blameable user value
 *
 * @author Ryan Jefferson <ryanhjefferson@gmail.com>
 */
final class FromListener
{
    private $blameableListener;

    public function __construct(BlameableListener $blameableListener)
    {
        $this->blameableListener = $blameableListener;
    }

    /**
     * Get "From" header and set as user value
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $fromHeader = $request->headers->get('From');
        if (!empty($fromHeader)) {
            $user = new FromUser($fromHeader);
            $this->blameableListener->setUserValue($user);
        }
    }
}
