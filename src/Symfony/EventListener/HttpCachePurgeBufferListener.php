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

namespace ApiPlatform\Symfony\EventListener;

use ApiPlatform\HttpCache\BufferedPurger;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

final class HttpCachePurgeBufferListener
{
    public function __construct(private readonly BufferedPurger $purger)
    {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($event->isMainRequest()) {
            $this->purger->startBuffering();
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if ($event->isMainRequest()) {
            $this->purger->flush();
        }
    }
}
