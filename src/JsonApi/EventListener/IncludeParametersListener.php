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

namespace ApiPlatform\Core\JsonApi\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @internal
 *
 * @see http://jsonapi.org/format/#fetching-includes
 */
final class IncludeParametersListener
{
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (
            'jsonapi' !== $request->getRequestFormat() ||
            !$request->attributes->get('_api_resource_class') ||
            !$request->query->get('include')
        ) {
            return;
        }

        throw new BadRequestHttpException('Inclusion of related resources is not supported yet.');
    }
}
