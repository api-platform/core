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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ContainNonResource as ContainNonResourceDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Taxon as TaxonDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ContainNonResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5094Relation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ResourceInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Taxon;
use ApiPlatform\Tests\Fixtures\TestBundle\Model\ResourceInterface as ResourceInterfaceDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Model\SerializableResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Model\TaxonInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\State\ContainNonResourceProvider;
use ApiPlatform\Tests\Fixtures\TestBundle\State\Issue5094RelationProvider;
use ApiPlatform\Tests\Fixtures\TestBundle\State\ResourceInterfaceImplementationProvider;
use ApiPlatform\Tests\Fixtures\TestBundle\State\SerializableProvider;
use ApiPlatform\Tests\Fixtures\TestBundle\State\TaxonItemProvider;

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

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        if (ResourceInterface::class === $resourceClass || ResourceInterfaceDocument::class === $resourceClass) {
            return $this->setProvider($resourceMetadataCollection, ResourceInterfaceImplementationProvider::class);
        }

        if (ContainNonResource::class === $resourceClass || ContainNonResourceDocument::class === $resourceClass) {
            return $this->setProvider($resourceMetadataCollection, ContainNonResourceProvider::class);
        }

        if (SerializableResource::class === $resourceClass) {
            return $this->setProvider($resourceMetadataCollection, SerializableProvider::class);
        }

        if (Taxon::class === $resourceClass || TaxonDocument::class === $resourceClass || TaxonInterface::class === $resourceClass) {
            return $this->setProvider($resourceMetadataCollection, TaxonItemProvider::class);
        }

        if (Issue5094Relation::class === $resourceClass) {
            return $this->setProvider($resourceMetadataCollection, Issue5094RelationProvider::class);
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
