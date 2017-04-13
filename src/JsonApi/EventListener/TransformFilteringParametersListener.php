<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\JsonApi\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Flattens possible 'filter' array query parameter into first-level query parameters
 * to be processed by api-platform.
 *
 * See: http://jsonapi.org/format/#fetching-filtering and http://jsonapi.org/recommendations/#filtering
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

        // If page query parameter is not defined or is not an array, never mind
        if (!$request->query->get('filter') || !is_array($request->query->get('filter'))) {
            return;
        }

        // Otherwise, flatten into dot-separated values
        $pageParameters = $request->query->get('filter');

        foreach ($pageParameters as $pageParameterName => $pageParameterValue) {
            $request->query->set(
                $pageParameterName,
                $pageParameterValue
            );
        }

        $request->query->remove('filter');
    }
}
