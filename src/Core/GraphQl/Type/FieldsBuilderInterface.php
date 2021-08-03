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

namespace ApiPlatform\Core\GraphQl\Type;

use ApiPlatform\Metadata\GraphQl\Operation;

/**
 * Interface implemented to build GraphQL fields.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface FieldsBuilderInterface
{
    /**
     * Gets the fields of a node for a query.
     */
    public function getNodeQueryFields(): array;

    /**
     * Gets the item query fields of the schema.
     */
    public function getItemQueryFields(string $resourceClass, Operation $operation, string $queryName, array $configuration): array;

    /**
     * Gets the collection query fields of the schema.
     */
    public function getCollectionQueryFields(string $resourceClass, Operation $operation, string $queryName, array $configuration): array;

    /**
     * Gets the mutation fields of the schema.
     */
    public function getMutationFields(string $resourceClass, Operation $operation, string $mutationName): array;

    /**
     * Gets the subscription fields of the schema.
     */
    public function getSubscriptionFields(string $resourceClass, Operation $operation, string $subscriptionName): array;

    /**
     * Gets the fields of the type of the given resource.
     */
    public function getResourceObjectTypeFields(?string $resourceClass, Operation $operation, bool $input, string $operationName, int $depth = 0, ?array $ioMetadata = null): array;

    /**
     * Resolve the args of a resource by resolving its types.
     */
    public function resolveResourceArgs(array $args, string $operationName, string $shortName): array;
}
