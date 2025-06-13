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

use ApiPlatform\Metadata\Extractor\ResourceExtractorInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

final class PhpFileResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    use OperationDefaultsTrait;

    public function __construct(
        private readonly ResourceExtractorInterface $metadataExtractor,
        private readonly ?ResourceMetadataCollectionFactoryInterface $decorated = null,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = new ResourceMetadataCollection($resourceClass);
        if ($this->decorated) {
            $resourceMetadataCollection = $this->decorated->create($resourceClass);
        }

        foreach ($this->metadataExtractor->getResources() as $resource) {
            if ($resourceClass !== $resource->getClass()) {
                continue;
            }

            $shortName = (false !== $pos = strrpos($resourceClass, '\\')) ? substr($resourceClass, $pos + 1) : $resourceClass;
            $resource = $this->getResourceWithDefaults($resourceClass, $shortName, $resource);

            $operations = [];
            /** @var Operation $operation */
            foreach ($resource->getOperations() ?? new Operations() as $operation) {
                [$key, $operation] = $this->getOperationWithDefaults($resource, $operation);
                $operations[$key] = $operation;
            }

            if ($operations) {
                $resource = $resource->withOperations(new Operations($operations));
            }

            $resourceMetadataCollection[] = $resource;
        }

        return $resourceMetadataCollection;
    }
}
