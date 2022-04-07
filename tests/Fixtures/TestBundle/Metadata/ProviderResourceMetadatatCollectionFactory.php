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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Metadata;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ResourceInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Model\ResourceInterface as OdmResourceInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\State\ResourceInterfaceImplementationProvider;

class ProviderResourceMetadatatCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    /**
     * @var ResourceMetadataCollectionFactoryInterface
     */
    private $decorated;

    public function __construct(ResourceMetadataCollectionFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritDoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        if ($resourceClass === ResourceInterface::class || $resourceClass === OdmResourceInterface::class) {
            return $this->setProvider($resourceMetadataCollection, ResourceInterfaceImplementationProvider::class);
        }

        return $resourceMetadataCollection;
    }

    private function setProvider(ResourceMetadataCollection $resourceMetadataCollection, string $provider)
    {
        foreach ($resourceMetadataCollection as $i => $resourceMetadata) {
            $operations = $resourceMetadata->getOperations();

            if ($operations) {
                foreach ($resourceMetadata->getOperations() as $operationName => $operation) {
                    $operations->add($operationName, $operation->withProvider($provider));
                }

                $resourceMetadata = $resourceMetadata->withOperations($operations);
            }

            $graphQlOperations = $resourceMetadata->getGraphQlOperations();

            if ($graphQlOperations) {
                foreach ($graphQlOperations as $operationName => $graphQlOperation) {
                    $graphQlOperations[$operationName] = $graphQlOperation->withProvider($provider);
                }

                $resourceMetadata = $resourceMetadata->withGraphQlOperations($graphQlOperations);
            }

            $resourceMetadataCollection[$i] = $resourceMetadata;
        }

        return $resourceMetadataCollection;
    }
}
