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
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\WrappingType;
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
    public function __construct(private readonly ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, private readonly TypesFactoryInterface $typesFactory, private readonly TypesContainerInterface $typesContainer, private readonly FieldsBuilderInterface $fieldsBuilder)
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

        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            $resourceMetadataCollection = $this->resourceMetadataCollectionFactory->create($resourceClass);
            foreach ($resourceMetadataCollection as $resourceMetadata) {
                foreach ($resourceMetadata->getGraphQlOperations() ?? [] as $operation) {
                    $configuration = null !== $operation->getArgs() ? ['args' => $operation->getArgs()] : [];

                    if ($operation instanceof Query && $operation instanceof CollectionOperationInterface) {
                        $queryFields += $this->fieldsBuilder->getCollectionQueryFields($resourceClass, $operation, $configuration);

                        continue;
                    }

                    if ($operation instanceof Query) {
                        $queryFields += $this->fieldsBuilder->getItemQueryFields($resourceClass, $operation, $configuration);

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

        $schema = [
            'query' => new ObjectType([
                'name' => 'Query',
                'fields' => $queryFields,
            ]),
            'typeLoader' => function ($name): Type {
                $type = $this->typesContainer->get($name);

                if ($type instanceof WrappingType) {
                    return $type->getWrappedType(true);
                }

                return $type;
            },
        ];

        if ($mutationFields) {
            $schema['mutation'] = new ObjectType([
                'name' => 'Mutation',
                'fields' => $mutationFields,
            ]);
        }

        if ($subscriptionFields) {
            $schema['subscription'] = new ObjectType([
                'name' => 'Subscription',
                'fields' => $subscriptionFields,
            ]);
        }

        return new Schema($schema);
    }
}
