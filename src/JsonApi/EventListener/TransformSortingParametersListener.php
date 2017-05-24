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
 * Converts pagination parameters from JSON API recommended convention to
 * api-platform convention.
 *
 * @see http://jsonapi.org/format/#fetching-sorting
 * @see https://api-platform.com/docs/core/filters#order-filter
 *
 * @author Héctor Hurtarte <hectorh30@gmail.com>
 */
final class TransformSortingParametersListener
{
    private $orderParameterName;

    public function __construct(string $orderParameterName)
    {
        $this->orderParameterName = $orderParameterName;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        // This applies only to jsonapi request format
        if ('jsonapi' !== $request->getRequestFormat()) {
            return;
        }

        // If order query parameter is not defined or is already an array, never mind
        $orderParameter = $request->query->get($this->orderParameterName);
        if (null === $orderParameter || is_array($orderParameter)) {
            return;
        }

        $orderParametersArray = explode(',', $orderParameter);

        $transformedOrderParametersArray = [];
        foreach ($orderParametersArray as $orderParameter) {
            $sorting = 'asc';

            if ('-' === substr($orderParameter, 0, 1)) {
                $sorting = 'desc';
                $orderParameter = substr($orderParameter, 1);
            }

            $transformedOrderParametersArray[$orderParameter] = $sorting;
        }

        $request->query->set($this->orderParameterName, $transformedOrderParametersArray);
    }
}
