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

use Symfony\Component\PropertyInfo\Type;

/**
 * Gets the OpenAPI type corresponding to the given PHP type, and recursively adds needed new schema to the current schema if provided.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface TypeFactoryInterface
{
    public function getType(Type $type, string $format = 'json', ?bool $readableLink = null, ?array $serializerContext = null, Schema $schema = null): array;
}
