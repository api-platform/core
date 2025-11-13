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

namespace ApiPlatform\Doctrine\Odm\Metadata\Resource;

use ApiPlatform\Doctrine\Odm\State\CollectionProvider;
use ApiPlatform\Doctrine\Odm\State\ItemProvider;
use ApiPlatform\Doctrine\Odm\State\Options;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\State\Util\StateOptionsTrait;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;

final class DoctrineMongoDbOdmResourceCollectionMetadataFactory implements ResourceMetadataCollectionFactoryInterface
{
    use StateOptionsTrait;

    public function __construct(private readonly ManagerRegistry $managerRegistry, private readonly ResourceMetadataCollectionFactoryInterface $decorated)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        foreach ($resourceMetadataCollection as $i => $resourceMetadata) {
            $operations = $resourceMetadata->getOperations();

            if ($operations) {
                foreach ($resourceMetadata->getOperations() as $operationName => $operation) {
                    $documentClass = $this->getStateOptionsClass($operation, $operation->getClass(), Options::class);
                    if (!$this->managerRegistry->getManagerForClass($documentClass) instanceof DocumentManager) {
                        continue;
                    }

                    $operations->add($operationName, $this->addDefaults($operation));
                }

                $resourceMetadata = $resourceMetadata->withOperations($operations);
            }

            $graphQlOperations = $resourceMetadata->getGraphQlOperations();

            if ($graphQlOperations) {
                foreach ($graphQlOperations as $operationName => $graphQlOperation) {
                    if (!$this->managerRegistry->getManagerForClass($graphQlOperation->getClass()) instanceof DocumentManager) {
                        continue;
                    }

                    $graphQlOperations[$operationName] = $this->addDefaults($graphQlOperation);
                }

                $resourceMetadata = $resourceMetadata->withGraphQlOperations($graphQlOperations);
            }

            $resourceMetadataCollection[$i] = $resourceMetadata;
        }

        return $resourceMetadataCollection;
    }

    private function addDefaults(Operation $operation): Operation
    {
        $options = $operation->getStateOptions() ?: new Options();
        if ($options instanceof Options && null === $options->getHandleLinks()) {
            $options = $options->withHandleLinks('api_platform.doctrine.odm.links_handler');
            $operation = $operation->withStateOptions($options);
        }

        if (null === $operation->getProvider()) {
            $operation = $operation->withProvider($this->getProvider($operation));

            if ($operation instanceof HttpOperation) {
                $operation = $operation->withRequirements($this->getRequirements($operation));
            }
        }

        if (null === $operation->getProcessor()) {
            $operation = $operation->withProcessor($this->getProcessor($operation));
        }

        return $operation;
    }

    /**
     * @return array<string, string>
     */
    private function getRequirements(HttpOperation $operation): array
    {
        $requirements = $operation->getRequirements() ?? [];
        $uriVariables = (array) ($operation->getUriVariables() ?? []);

        foreach ($uriVariables as $paramName => $uriVariable) {
            if (isset($requirements[$paramName])) {
                continue;
            }

            if (!$uriVariable instanceof Link) {
                continue;
            }

            $identifiers = $uriVariable->getIdentifiers();
            if (1 !== \count($identifiers)) {
                continue;
            }
            $fieldName = $identifiers[0];

            $fromClass = $uriVariable->getFromClass();
            if (null === $fromClass) {
                continue;
            }
            $classMetadata = $this->managerRegistry->getManagerForClass($fromClass)?->getClassMetadata($fromClass);

            $requirement = null;
            if ($classMetadata instanceof ClassMetadata && $classMetadata->hasField($fieldName)) {
                $fieldMapping = $classMetadata->getFieldMapping($fieldName);
                $requirement = match ($fieldMapping['type']) {
                    'uuid', 'guid' => '^[0-9a-fA-F]{8}(?:-[0-9a-fA-F]{4}){3}-[0-9a-fA-F]{12}$',
                    'ulid' => '^[0-7][0-9a-hjkmnp-tv-zA-HJKMNP-TV-Z]{25}$',
                    'smallint', 'integer', 'bigint' => '^-?[0-9]+$',
                    default => null,
                };
            }

            if (null !== $requirement) {
                $requirements[$paramName] = $requirement;
            }
        }

        return $requirements;
    }

    private function getProvider(Operation $operation): string
    {
        if ($operation instanceof CollectionOperationInterface) {
            return CollectionProvider::class;
        }

        return ItemProvider::class;
    }

    private function getProcessor(Operation $operation): string
    {
        if ($operation instanceof DeleteOperationInterface) {
            return 'api_platform.doctrine_mongodb.odm.state.remove_processor';
        }

        return 'api_platform.doctrine_mongodb.odm.state.persist_processor';
    }
}
