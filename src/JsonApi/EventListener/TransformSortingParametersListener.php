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
 * See: http://jsonapi.org/format/#fetching-sorting and
 * https://api-platform.com/docs/core/filters#order-filter
 *
 * @author Héctor Hurtarte <hectorh30@gmail.com>
 */
final class TransformSortingParametersListener
{
    /**
     * @var string Keyword used to retrieve the value
     */
    private $orderParameterName;

    public function __construct(string $orderParameterName)
    {
        $this->orderParameterName = $orderParameterName;
    }

    /**
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

        // If order query parameter is not defined or is already an array, never mind
        if (
            !$request->query->get($this->orderParameterName)
                || is_array($request->query->get($this->orderParameterName))
        ) {
            return;
        }

        $orderParametersArray = explode(',', $request->query->get($this->orderParameterName));

        $transformedOrderParametersArray = [];
        foreach ($orderParametersArray as $orderParameter) {
            $sorting = 'asc';

            if ('-' === substr($orderParameter, 0, 1)) {
                $sorting = 'desc';
                $orderParameter = substr($orderParameter, 1);
            }

            $transformedOrderParametersArray[$orderParameter] = $sorting;
        }

        $request->query->set(
            $this->orderParameterName,
            $transformedOrderParametersArray
        );
    }
}
