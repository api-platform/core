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

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Flattens possible 'page' array query parameter into dot-separated values to avoid
 * conflicts with Doctrine\Orm\Extension\PaginationExtension.
 *
 * See: http://jsonapi.org/format/#fetching-pagination
 *
 * @author Héctor Hurtarte <hectorh30@gmail.com>
 */
final class FlattenPaginationParametersListener
{
    /**
     * Flatens possible 'page' array query parameter
     *
     * @param GetResponseEvent $event
     *
     * @throws NotFoundHttpException
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        // If page query parameter is not defined or is not an array, never mind
        if (!$request->query->get('page') || !is_array($request->query->get('page'))) {
            return;
        }

        // Otherwise, flatten into dot-separated values
        $pageParameters = $request->query->get('page');

        foreach ($pageParameters as $pageParameterName => $pageParameterValue) {
            $request->query->set(
                sprintf('page.%s', $pageParameterName),
                $pageParameterValue
            );
        }

        $request->query->remove('page');
    }
}
