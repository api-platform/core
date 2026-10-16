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

namespace ApiPlatform\JsonSchema\Generator;

use ApiPlatform\JsonSchema\Metadata\Property\Factory\SchemaPropertyMetadataFactory;
use ApiPlatform\JsonSchema\Schema;
use Symfony\Component\JsonSchema\Configuration;
use Symfony\Component\JsonSchema\PropertySchemaProvider\PropertySchemaProviderInterface;
use Symfony\Component\JsonSchema\SchemaDocument;

final class ApiPropertySchemaProvider implements PropertySchemaProviderInterface
{
    public function __construct(
        private readonly PropertyMetadataAccessor $accessor,
    ) {
    }

    public function provide(string $class, string $property, Configuration $config, SchemaDocument $document): ?array
    {
        $metadata = $this->accessor->get($class, $property, PropertyMetadataAccessor::contextFromConfiguration($config));

        if (true !== ($metadata->getExtraProperties()[SchemaPropertyMetadataFactory::JSON_SCHEMA_USER_DEFINED] ?? false)) {
            return null;
        }

        $contextSchema = Schema::VERSION_JSON_SCHEMA === $config->context[ConfigurationFactory::CONTEXT_VERSION] ? $metadata->getJsonSchemaContext() : $metadata->getOpenapiContext();

        return array_merge($metadata->getSchema() ?? [], $contextSchema ?? []);
    }
}
