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
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class ChainSubresourceDataProvider implements SubresourceDataProviderInterface
{
    private $dataProviders;

    /**
     * @param SubresourceDataProviderInterface[] $dataProviders
     */
    public function __construct(array $dataProviders)
    {
        $this->dataProviders = $dataProviders;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubresource(string $resourceClass, array $identifiers, array $context, string $operationName = null)
    {
        foreach ($this->dataProviders as $dataProviders) {
            try {
                return $dataProviders->getSubresource($resourceClass, $identifiers, $context, $operationName);
            } catch (ResourceClassNotSupportedException $e) {
                continue;
            }
        }
    }
}
