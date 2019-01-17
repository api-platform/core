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

/**
 * @see http://jsonapi.org/format/#fetching-pagination
 * @see https://api-platform.com/docs/core/pagination
 *
 * @author Héctor Hurtarte <hectorh30@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class TransformPaginationParametersListener
{
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (
            'jsonapi' !== $request->getRequestFormat() ||
            null === ($pageParameter = $request->query->get('page')) ||
            !\is_array($pageParameter)
        ) {
            return;
        }

        $filters = $request->attributes->get('_api_filters', []);
        $request->attributes->set('_api_filters', array_merge($pageParameter, $filters));

        /* @TODO remove the `_api_pagination` attribute in 3.0 (@meyerbaptiste) */
        $request->attributes->set('_api_pagination', $pageParameter);
    }
}
