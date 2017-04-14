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

namespace ApiPlatform\Core\DataProvider;

use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;

/**
 * Tries each configured data provider and returns the result of the first able to handle the resource class.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ChainCollectionDataProvider implements CollectionDataProviderInterface
{
    private $dataProviders;

    /**
     * @param CollectionDataProviderInterface[] $dataProviders
     */
    public function __construct(array $dataProviders)
    {
        $this->dataProviders = $dataProviders;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(string $resourceClass, string $operationName = null)
    {
        foreach ($this->dataProviders as $dataProvider) {
            try {
                return $dataProvider->getCollection($resourceClass, $operationName);
            } catch (ResourceClassNotSupportedException $e) {
                continue;
            }
        }
    }
}
