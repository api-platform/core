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

namespace ApiPlatform\Metadata\Property\Factory;

use ApiPlatform\JsonSchema\Metadata\Property\Factory\SchemaPropertyMetadataFactory;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Exception\PropertyNotFoundException;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Extractor\PropertyExtractorInterface;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Type as LegacyType;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeResolver\StringTypeResolver;

/**
 * Creates properties's metadata using an extractor.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class ExtractorPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    public function __construct(private readonly PropertyExtractorInterface $extractor, private readonly ?PropertyMetadataFactoryInterface $decorated = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = []): ApiProperty
    {
        $parentPropertyMetadata = null;
        if ($this->decorated) {
            try {
                $parentPropertyMetadata = $this->decorated->create($resourceClass, $property, $options);
            } catch (PropertyNotFoundException) {
                // Ignore not found exception from decorated factories
            }
        }

        if (
            !property_exists($resourceClass, $property) && !interface_exists($resourceClass)
            || null === ($propertyMetadata = $this->extractor->getProperties()[$resourceClass][$property] ?? null)
        ) {
            return $this->handleNotFound($parentPropertyMetadata, $resourceClass, $property);
        }

        if ($parentPropertyMetadata) {
            return $this->handleUserDefinedSchema($this->update($parentPropertyMetadata, $propertyMetadata));
        }

        $apiProperty = new ApiProperty();

        foreach ($propertyMetadata as $key => $value) {
            if ('builtinTypes' === $key && null !== $value) {
                if (method_exists(PropertyInfoExtractor::class, 'getType')) {
                    continue;
                }

                $apiProperty = $apiProperty->withBuiltinTypes(array_map(static fn (string $builtinType): LegacyType => new LegacyType($builtinType), $value));

                continue;
            }

            if ('nativeType' === $key && null !== $value) {
                if (class_exists(PhpDocParser::class)) {
                    $apiProperty = $apiProperty->withNativeType((new StringTypeResolver())->resolve($value));

                    continue;
                }

                try {
                    $apiProperty = $apiProperty->withNativeType(Type::builtin($value));
                } catch (\ValueError) {
                    throw new RuntimeException(\sprintf('Cannot create a type from "%s". Try running "composer require phpstan/phpdoc-parser" to support all types.', $value));
                }

                continue;
            }

            $methodName = 'with'.ucfirst($key);

            if (method_exists($apiProperty, $methodName) && null !== $value) {
                $apiProperty = $apiProperty->{$methodName}($value);
            }
        }

        return $this->handleUserDefinedSchema($apiProperty);
    }

    /**
     * Returns the metadata from the decorated factory if available or throws an exception.
     *
     * @throws PropertyNotFoundException
     */
    private function handleNotFound(?ApiProperty $parentPropertyMetadata, string $resourceClass, string $property): ApiProperty
    {
        if ($parentPropertyMetadata) {
            return $parentPropertyMetadata;
        }

        throw new PropertyNotFoundException(\sprintf('Property "%s" of the resource class "%s" not found.', $property, $resourceClass));
    }

    /**
     * Creates a new instance of metadata if the property is not already set.
     */
    private function update(ApiProperty $propertyMetadata, array $metadata): ApiProperty
    {
        foreach (get_class_methods(ApiProperty::class) as $method) {
            if (preg_match('/^(?:get|is)(.*)/', (string) $method, $matches) && null !== ($val = $metadata[lcfirst($matches[1])] ?? null) && method_exists($propertyMetadata, "with{$matches[1]}")) {
                $propertyMetadata = $propertyMetadata->{"with{$matches[1]}"}($val);
            }
        }

        return $propertyMetadata;
    }

    private function handleUserDefinedSchema(ApiProperty $propertyMetadata): ApiProperty
    {
        // can't know later if the schema has been defined by the user or by API Platform
        // store extra key to make this difference
        if (null !== $propertyMetadata->getSchema()) {
            $extraProperties = $propertyMetadata->getExtraProperties();
            $propertyMetadata = $propertyMetadata->withExtraProperties([SchemaPropertyMetadataFactory::JSON_SCHEMA_USER_DEFINED => true] + $extraProperties);
        }

        return $propertyMetadata;
    }
}
