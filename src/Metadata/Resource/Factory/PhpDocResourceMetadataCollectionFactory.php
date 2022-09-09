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
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use phpDocumentor\Reflection\Types\ContextFactory;

/**
 * Extracts descriptions from PHPDoc.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class PhpDocResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    private $decorated;
    private $docBlockFactory;
    private $contextFactory;

    public function __construct(ResourceMetadataCollectionFactoryInterface $decorated, DocBlockFactoryInterface $docBlockFactory = null)
    {
        $this->decorated = $decorated;
        $this->docBlockFactory = $docBlockFactory ?: DocBlockFactory::createInstance();
        $this->contextFactory = new ContextFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        foreach ($resourceMetadataCollection as $key => $resourceMetadata) {
            if (null !== $resourceMetadata->getDescription()) {
                continue;
            }

            $reflectionClass = new \ReflectionClass($resourceClass);

            try {
                $docBlock = $this->docBlockFactory->create($reflectionClass, $this->contextFactory->createFromReflector($reflectionClass));
                $resourceMetadataCollection[$key] = $resourceMetadata->withDescription($docBlock->getSummary());

                $operations = $resourceMetadata->getOperations() ?? new Operations();
                foreach ($operations as $operationName => $operation) {
                    if (null !== $operation->getDescription()) {
                        continue;
                    }

                    $operations->add($operationName, $operation->withDescription($docBlock->getSummary()));
                }

                $resourceMetadataCollection[$key] = $resourceMetadataCollection[$key]->withOperations($operations);

                if (!$resourceMetadata->getGraphQlOperations()) {
                    continue;
                }

                foreach ($graphQlOperations = $resourceMetadata->getGraphQlOperations() as $operationName => $operation) {
                    if (null !== $operation->getDescription()) {
                        continue;
                    }

                    $graphQlOperations[$operationName] = $operation->withDescription($docBlock->getSummary());
                }

                $resourceMetadataCollection[$key] = $resourceMetadataCollection[$key]->withGraphQlOperations($graphQlOperations);
            } catch (\InvalidArgumentException $e) {
                // Ignore empty DocBlocks
            }
        }

        return $resourceMetadataCollection;
    }
}
