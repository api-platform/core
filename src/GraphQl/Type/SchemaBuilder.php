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

namespace ApiPlatform\GraphQl\Type;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;

/**
 * Builds the GraphQL schema.
 *
 * @author Raoul Clais <raoul.clais@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class SchemaBuilder implements SchemaBuilderInterface
{
    public function __construct(private readonly ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, private readonly TypesFactoryInterface $typesFactory, private readonly TypesContainerInterface $typesContainer, private readonly FieldsBuilderEnumInterface $fieldsBuilder)
    {
    }

    public function getSchema(): Schema
    {
        $types = $this->typesFactory->getTypes();
        foreach ($types as $typeId => $type) {
            $this->typesContainer->set($typeId, $type);
        }

        $queryFields = ['node' => $this->fieldsBuilder->getNodeQueryFields()];
        $mutationFields = [];
        $subscriptionFields = [];
        $deferredQueryOperations = [];

        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            $resourceMetadataCollection = $this->resourceMetadataCollectionFactory->create($resourceClass);
            foreach ($resourceMetadataCollection as $resourceMetadata) {
                foreach ($resourceMetadata->getGraphQlOperations() ?? [] as $operation) {
                    $configuration = null !== $operation->getArgs() ? ['args' => $operation->getArgs()] : [];

                    if ($operation instanceof Query && $operation instanceof CollectionOperationInterface) {
                        try {
                            $queryFields += $this->fieldsBuilder->getCollectionQueryFields($resourceClass, $operation, $configuration);
                        } catch (InvalidArgumentException) {
                            $deferredQueryOperations[] = [$resourceClass, $operation, $configuration, true];
                        }

                        continue;
                    }

                    if ($operation instanceof Query) {
                        try {
                            $queryFields += $this->fieldsBuilder->getItemQueryFields($resourceClass, $operation, $configuration);
                        } catch (InvalidArgumentException) {
                            $deferredQueryOperations[] = [$resourceClass, $operation, $configuration, false];
                        }

                        continue;
                    }

                    if ($operation instanceof Subscription && $operation->getMercure()) {
                        $subscriptionFields += $this->fieldsBuilder->getSubscriptionFields($resourceClass, $operation);

                        continue;
                    }

                    $mutationFields += $this->fieldsBuilder->getMutationFields($resourceClass, $operation);
                }
            }
        }

        if ($deferredQueryOperations) {
            $this->materializeRegisteredTypes();

            foreach ($deferredQueryOperations as [$resourceClass, $operation, $configuration, $isCollectionQuery]) {
                try {
                    $queryFields += $isCollectionQuery
                        ? $this->fieldsBuilder->getCollectionQueryFields($resourceClass, $operation, $configuration)
                        : $this->fieldsBuilder->getItemQueryFields($resourceClass, $operation, $configuration);
                } catch (InvalidArgumentException $e) {
                    throw new InvalidArgumentException(\sprintf('Custom argument(s) "%s" of GraphQL operation "%s" for resource "%s" could not be resolved, even after building every resource type: %s', implode('", "', array_keys($configuration['args'] ?? [])), $operation->getName(), $operation->getShortName(), $e->getMessage()), 0, $e);
                }
            }
        }

        $queryType = new ObjectType([
            'name' => 'Query',
            'fields' => $queryFields,
        ]);
        $this->typesContainer->set('Query', $queryType);

        $schema = [
            'query' => $queryType,
            'typeLoader' => function (string $typeName): ?NamedType {
                try {
                    $type = $this->typesContainer->get($typeName);
                } catch (TypeNotFoundException) {
                    return null;
                }

                return Type::getNamedType($type);
            },
        ];

        if ($mutationFields) {
            $mutationType = new ObjectType([
                'name' => 'Mutation',
                'fields' => $mutationFields,
            ]);
            $this->typesContainer->set('Mutation', $mutationType);

            $schema['mutation'] = $mutationType;
        }

        if ($subscriptionFields) {
            $subscriptionType = new ObjectType([
                'name' => 'Subscription',
                'fields' => $subscriptionFields,
            ]);
            $this->typesContainer->set('Subscription', $subscriptionType);

            $schema['subscription'] = $subscriptionType;
        }

        return new Schema($schema);
    }

    private function materializeRegisteredTypes(): void
    {
        $visited = [];

        do {
            $before = \count($this->typesContainer->all());

            foreach ($this->typesContainer->all() as $id => $type) {
                if (isset($visited[$id])) {
                    continue;
                }

                $visited[$id] = true;

                if ($type instanceof ObjectType || $type instanceof InputObjectType) {
                    $type->getFields();
                }
            }
        } while (\count($this->typesContainer->all()) > $before);
    }
}
