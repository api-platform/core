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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Routing\RequestContext;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class RouterListener
{
    private $context;

    public function __construct(RequestContext $context)
    {
        $this->context = $context;
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if ($event->getRequest()->isMethod(Request::METHOD_OPTIONS)) {
            $this->context->fromRequest($event->getRequest());
        }
    }
}
