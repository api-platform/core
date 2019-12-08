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

use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * @see http://jsonapi.org/format/#fetching-filtering
 * @see http://jsonapi.org/recommendations/#filtering
 *
 * @author Héctor Hurtarte <hectorh30@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class TransformFilteringParametersListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (
            'jsonapi' !== $request->getRequestFormat() ||
            null === ($filterParameter = $request->query->get('filter')) ||
            !\is_array($filterParameter)
        ) {
            return;
        }
        $filters = $request->attributes->get('_api_filters', []);
        $request->attributes->set('_api_filters', array_merge($filterParameter, $filters));
    }
}
