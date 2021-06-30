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

namespace ApiPlatform\Hydra\JsonSchema;

use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\TypeFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use Symfony\Component\PropertyInfo\Type;

final class TypeFactory implements TypeFactoryInterface
{
    public function __construct(private readonly TypeFactoryInterface $typeFactory)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getType(Type $type, string $format = 'json', ?bool $readableLink = null, ?array $serializerContext = null, Schema $schema = null): array
    {
        $jsonSchemaType = $this->typeFactory->getType($type, $format, $readableLink, $serializerContext, $schema);

        return $this->addAllTranslationsToTypeDefinition($jsonSchemaType);
    }

    /**
     * @param array<string, mixed> $jsonSchema
     *
     * @return array<string, mixed>
     */
    private function addAllTranslationsToTypeDefinition(array $jsonSchema): array
    {
        return [
            'oneOf' => [
                $jsonSchema,
                // @phpstan-ignore-next-line Remove the condition when we use 3.1.0.
                OpenApi::VERSION !== '3.0.0' ? ['type' => 'object', 'patternProperties' => ['.+' => $jsonSchema]] : ['type' => 'object'],
            ],
        ];
    }
}
