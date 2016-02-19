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

use ApiPlatform\Builder\Api\CollectionDataProviderInterface;
use ApiPlatform\Builder\Api\PaginatorInterface;
use ApiPlatform\Builder\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Default API action retrieving a collection of resources.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class GetCollectionAction
{
    use ActionUtilTrait;

    private $collectionDataProvider;

    public function __construct(CollectionDataProviderInterface $collectionDataProvider)
    {
        $this->collectionDataProvider = $collectionDataProvider;
    }

    /**
     * Retrieves a collection of resources.
     *
     * @param Request $request
     *
     * @return array|PaginatorInterface|\Traversable
     *
     * @throws RuntimeException
     */
    public function __invoke(Request $request)
    {
        list($resourceClass, $operationName) = $this->extractAttributes($request);

        return $this->collectionDataProvider->getCollection($resourceClass, $operationName);
    }
}
