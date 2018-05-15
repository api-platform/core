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
 * @see http://jsonapi.org/format/#fetching-sorting
 * @see https://api-platform.com/docs/core/filters#order-filter
 *
 * @author Héctor Hurtarte <hectorh30@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class TransformSortingParametersListener
{
    private $orderParameterName;

    public function __construct(string $orderParameterName = 'order')
    {
        $this->orderParameterName = $orderParameterName;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (
            'jsonapi' !== $request->getRequestFormat() ||
            null === ($orderParameter = $request->query->get('sort')) ||
            \is_array($orderParameter)
        ) {
            return;
        }

        $orderParametersArray = explode(',', $orderParameter);
        $transformedOrderParametersArray = [];

        foreach ($orderParametersArray as $orderParameter) {
            $sorting = 'asc';

            if ('-' === ($orderParameter[0] ?? null)) {
                $sorting = 'desc';
                $orderParameter = substr($orderParameter, 1);
            }

            $transformedOrderParametersArray[$orderParameter] = $sorting;
        }

        $filters = $request->attributes->get('_api_filters', []);
        $filters[$this->orderParameterName] = $transformedOrderParametersArray;
        $request->attributes->set('_api_filters', $filters);
    }
}
