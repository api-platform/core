<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Action;

use ApiPlatform\Core\Api\RequestAttributesExtractor;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Default API action retrieving a resource (used for GET and DELETE methods).
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class GetItemAction
{
    private $itemDataProvider;

    public function __construct(ItemDataProviderInterface $itemDataProvider)
    {
        $this->itemDataProvider = $itemDataProvider;
    }

    /**
     * Retrieves an item.
     *
     * @param Request    $request
     * @param string|int $id
     *
     * @throws NotFoundHttpException
     * @throws RuntimeException
     *
     * @return mixed
     */
    public function __invoke(Request $request, $id)
    {
        list($resourceClass, , $operationName) = RequestAttributesExtractor::extractAttributes($request);

        $data = $this->itemDataProvider->getItem($resourceClass, $id, $operationName, true);
        if (!$data) {
            throw new NotFoundHttpException('Not Found');
        }

        return $data;
    }
}
