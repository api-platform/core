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

namespace ApiPlatform\Symfony\EventListener\JsonApi;

use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * @see http://jsonapi.org/format/#fetching-pagination
 * @see https://api-platform.com/docs/core/pagination
 *
 * @author Héctor Hurtarte <hectorh30@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class TransformPaginationParametersListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (($operation = $request->attributes->get('_api_operation')) && 'api_platform.symfony.main_controller' === $operation->getController()) {
            return;
        }

        $pageParameter = $request->query->all()['page'] ?? null;

        if (
            !\is_array($pageParameter)
            || 'jsonapi' !== $request->getRequestFormat()
        ) {
            return;
        }

        $filters = $request->attributes->get('_api_filters', []);
        $request->attributes->set('_api_filters', array_merge($pageParameter, $filters));
    }
}
