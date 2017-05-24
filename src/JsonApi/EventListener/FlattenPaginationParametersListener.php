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
 * Flattens possible 'page' array query parameter into dot-separated values to avoid
 * conflicts with {@see \ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension}.
 *
 * @see http://jsonapi.org/format/#fetching-pagination
 *
 * @author Héctor Hurtarte <hectorh30@gmail.com>
 */
final class FlattenPaginationParametersListener
{
    /**
     * Flattens possible 'page' array query parameter.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        // This applies only to jsonapi request format
        if ('jsonapi' !== $request->getRequestFormat()) {
            return;
        }

        // If 'page' query parameter is not defined or is not an array, never mind
        $page = $request->query->get('page');

        if (null === $page || !is_array($page)) {
            return;
        }

        // Otherwise, flatten into dot-separated values
        $pageParameters = $request->query->get('page');

        foreach ($pageParameters as $pageParameterName => $pageParameterValue) {
            $request->query->set("page.$pageParameterName", $pageParameterValue);
        }

        $request->query->remove('page');
    }
}
