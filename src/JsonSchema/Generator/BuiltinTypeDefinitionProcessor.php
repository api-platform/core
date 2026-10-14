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

use Symfony\Component\JsonSchema\Configuration;
use Symfony\Component\JsonSchema\DefinitionParent;
use Symfony\Component\JsonSchema\DefinitionProcessor\DefinitionProcessorInterface;
use Symfony\Component\JsonSchema\NullSyntax;
use Symfony\Component\JsonSchema\SchemaDocument;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\TypeIdentifier;

final class BuiltinTypeDefinitionProcessor implements DefinitionProcessorInterface
{
    private const STRUCTURAL_KEYWORDS = ['type', 'items', 'additionalProperties', 'anyOf', 'allOf', 'oneOf', '$ref', 'enum', 'const'];

    public function __construct(
        private readonly PropertyMetadataAccessor $accessor,
        private readonly ?NameConverterInterface $nameConverter = null,
    ) {
    }

    public function process(array $definition, string $class, Configuration $config, SchemaDocument $document, ?DefinitionParent $parent): array
    {
        if (!isset($definition['properties'])) {
            return $definition;
        }

        $context = PropertyMetadataAccessor::contextFromConfiguration($config);
        $nameConverterContext = array_filter(['groups' => $config->groups, 'attributes' => $config->attributes], static fn (?array $value): bool => null !== $value && [] !== $value);
        $string = self::nullableString($config->dialect->nullSyntax);

        foreach ($this->accessor->getNames($class, $context) as $property) {
            $name = $this->nameConverter?->normalize($property, $class, $config->format, $nameConverterContext) ?? $property;
            if (!isset($definition['properties'][$name])) {
                continue;
            }

            $type = $this->accessor->get($class, $property, $context)->getNativeType();
            while ($type instanceof NullableType) {
                $type = $type->getWrappedType();
            }

            $schema = $definition['properties'][$name];
            if ($type?->isIdentifiedBy(TypeIdentifier::MIXED) && !array_intersect_key($schema, array_flip(self::STRUCTURAL_KEYWORDS))) {
                $definition['properties'][$name] = [...$string, ...$schema];
            } elseif ($type instanceof CollectionType && $type->getCollectionValueType()->isIdentifiedBy(TypeIdentifier::MIXED) && [] === ($schema['items'] ?? null)) {
                $definition['properties'][$name]['items'] = $string;
            }
        }

        return $definition;
    }

    /**
     * @return array<string, mixed>
     */
    private static function nullableString(NullSyntax $syntax): array
    {
        return match ($syntax) {
            NullSyntax::Union => ['type' => ['string', 'null']],
            NullSyntax::NullableFlag => ['type' => 'string', 'nullable' => true],
            NullSyntax::Unsupported => ['type' => 'string'],
        };
    }
}
