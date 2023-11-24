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

namespace ApiPlatform\Metadata\Resource\Factory;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * Triggers resource deprecations.
 *
 * @final
 *
 * @internal
 */
class DeprecationResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    // Hashmap to avoid triggering too many deprecations
    private array $deprecated;

    public function __construct(private readonly ResourceMetadataCollectionFactoryInterface $decorated)
    {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        foreach ($resourceMetadataCollection as $i => $resourceMetadata) {
            $newOperations = [];
            foreach ($resourceMetadata->getOperations() as $operationName => $operation) {
                $extraProperties = $operation->getExtraProperties();
                if ($operation instanceof Put && null === ($extraProperties['standard_put'] ?? null)) {
                    $this->triggerDeprecationOnce($operation, 'extraProperties["standard_put"]', 'In API Platform 4 PUT will always replace the data, use extraProperties["standard_put"] to "true" on every operation to avoid breaking PUT\'s behavior. Use PATCH to use the old behavior.');
                }

                $newOperations[$operationName] = $operation;
            }

            $resourceMetadataCollection[$i] = $resourceMetadata->withOperations(new Operations($newOperations));
        }

        return $resourceMetadataCollection;
    }

    private function triggerDeprecationOnce(Operation $operation, string $deprecationName, string $deprecationReason): void
    {
        if (isset($this->deprecated[$operation->getClass().$deprecationName])) {
            return;
        }

        $this->deprecated[$operation->getClass().$deprecationName] = true;

        trigger_deprecation('api-platform/core', '3.1', $deprecationReason);
    }
}
