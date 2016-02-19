<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Builder\Action;

use ApiPlatform\Builder\Api\ItemDataProviderInterface;
use ApiPlatform\Builder\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Default API action retrieving a resource (used for GET and DELETE methods).
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class GetItemAction
{
    use ActionUtilTrait;

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
     * @return mixed
     *
     * @throws NotFoundHttpException
     * @throws RuntimeException
     */
    public function __invoke(Request $request, $id)
    {
        list($resourceClass, , $operationName) = $this->extractAttributes($request);

        return $this->getItem($this->itemDataProvider, $resourceClass, $operationName, $id);
    }
}
