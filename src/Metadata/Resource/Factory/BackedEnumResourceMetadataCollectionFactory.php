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

use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * Triggers resource deprecations.
 *
 * @internal
 */
final class BackedEnumResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public const PROVIDER = 'api_platform.state_provider.backed_enum';

    public function __construct(private readonly ResourceMetadataCollectionFactoryInterface $decorated)
    {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);
        if (!is_a($resourceClass, \BackedEnum::class, true)) {
            return $resourceMetadataCollection;
        }

        foreach ($resourceMetadataCollection as $i => $resourceMetadata) {
            $newOperations = [];
            foreach ($resourceMetadata->getOperations() ?? [] as $operationName => $operation) {
                $newOperations[$operationName] = $operation;

                if (null !== $operation->getProvider()) {
                    continue;
                }

                $newOperations[$operationName] = $operation->withProvider(self::PROVIDER);
            }

            $newGraphQlOperations = [];
            foreach ($resourceMetadata->getGraphQlOperations() ?? [] as $operationName => $operation) {
                $newGraphQlOperations[$operationName] = $operation;

                if (null !== $operation->getProvider()) {
                    continue;
                }

                $newGraphQlOperations[$operationName] = $operation->withProvider(self::PROVIDER);
            }

            $resourceMetadataCollection[$i] = $resourceMetadata->withOperations(new Operations($newOperations))->withGraphQlOperations($newGraphQlOperations);
        }

        return $resourceMetadataCollection;
    }
}
