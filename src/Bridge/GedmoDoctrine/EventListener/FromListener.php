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

use ApiPlatform\Core\Bridge\GedmoDoctrine\Model\FromUser;
use Gedmo\Blameable\BlameableListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Set the "From" header email address as a Blameable user value.
 *
 * @author Ryan Jefferson <ryanhjefferson@gmail.com>
 */
final class FromListener
{
    private $blameableListener;
    private $unmatched = [];

    public function __construct(BlameableListener $blameableListener, array $unmatched = null)
    {
        $this->blameableListener = $blameableListener;
        if (!empty($unmatched)) {
            $this->unmatched = $unmatched;
        }
    }

    /**
     * Get "From" header and set as user value.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $uri = $request->getRequestUri();
        foreach ($this->unmatched as $unmatch) {
            if (preg_match($unmatch, $uri)) {
                return;
            }
        }
        $fromHeader = $request->headers->get('From');
        if (!empty($fromHeader)) {
            $user = new FromUser($fromHeader);
            $this->blameableListener->setUserValue($user);
        }
    }
}
