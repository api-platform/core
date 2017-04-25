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
 * Flattens possible 'filter' array query parameter into first-level query parameters
 * to be processed by api-platform.
 *
 * @see http://jsonapi.org/format/#fetching-filtering and http://jsonapi.org/recommendations/#filtering
 *
 * @author Héctor Hurtarte <hectorh30@gmail.com>
 */
final class TransformFilteringParametersListener
{
    /**
     * Flatens possible 'page' array query parameter.
     *
     * @param GetResponseEvent $event
     *
     * @throws NotFoundHttpException
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        // This applies only to jsonapi request format
        if ('jsonapi' !== $request->getRequestFormat()) {
            return;
        }

        // If filter query parameter is not defined or is not an array, never mind
        $filter = $request->query->get('filter');

        if (null === $filter || !is_array($filter)) {
            return;
        }

        // Otherwise, flatten into dot-separated values
        $pageParameters = $filter;

        foreach ($pageParameters as $pageParameterName => $pageParameterValue) {
            $request->query->set(
                $pageParameterName,
                $pageParameterValue
            );
        }

        $request->query->remove('filter');
    }
}
