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

namespace ApiPlatform\Core\JsonSchema;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;

/**
 * Builds a JSON Schema from an API Platform resource definition.
 *
 * @experimental
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface SchemaFactoryInterface
{
    /**
     * @throws ResourceClassNotFoundException
     */
    public function buildSchema(string $resourceClass, string $format = 'json', string $type = Schema::TYPE_OUTPUT, ?string $operationType = null, ?string $operationName = null, ?Schema $schema = null, ?array $serializerContext = null, bool $forceCollection = false): Schema;
}
