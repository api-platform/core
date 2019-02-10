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

use ApiPlatform\Core\Event\EventInterface;
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

    /**
     * @deprecated since version 2.5, to be removed in 3.0.
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        @trigger_error(sprintf('The method %s() is deprecated since 2.5 and will be removed in 3.0.', __METHOD__), E_USER_DEPRECATED);

        $this->handleEvent($event);
    }

    public function handleEvent(/*EventInterface */$event): void
    {
        if ($event instanceof EventInterface) {
            $request = $event->getContext()['request'];
        } elseif ($event instanceof GetResponseEvent) {
            @trigger_error(sprintf('Passing an instance of "%s" as argument of "%s" is deprecated since 2.5 and will not be possible anymore in 3.0. Pass an instance of "%s" instead.', GetResponseEvent::class, __METHOD__, EventInterface::class), E_USER_DEPRECATED);

            $request = $event->getRequest();
        } else {
            return;
        }

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
