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

namespace ApiPlatform\Metadata\Operation\Factory;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Util\UriTemplateHelper;

final class OperationMetadataFactory implements OperationMetadataFactoryInterface
{
    private array $localCache = [];

    public function __construct(private readonly ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory)
    {
    }

    public function create(string $uriTemplate, array $context = []): ?Operation
    {
        if (isset($this->localCache[$uriTemplate])) {
            return $this->localCache[$uriTemplate];
        }

        $fallback = null;
        $strippedUriTemplate = UriTemplateHelper::withoutFormatSuffix($uriTemplate);

        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            foreach ($this->resourceMetadataCollectionFactory->create($resourceClass) as $resource) {
                foreach ($resource->getOperations() as $operation) {
                    if ($operation->getUriTemplate() === $uriTemplate || $operation->getName() === $uriTemplate) {
                        return $this->localCache[$uriTemplate] = $operation;
                    }

                    if (null === $fallback && null !== ($operationUriTemplate = $operation->getUriTemplate()) && UriTemplateHelper::withoutFormatSuffix($operationUriTemplate) === $strippedUriTemplate) {
                        $fallback = $operation;
                    }
                }
            }
        }

        if (null !== $fallback) {
            return $this->localCache[$uriTemplate] = $fallback;
        }

        return null;
    }
}
