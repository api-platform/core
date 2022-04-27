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

use ApiPlatform\Exception\PropertyNotFoundException;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Extractor\PropertyExtractorInterface;
use ApiPlatform\Metadata\Property\DeprecationMetadataTrait;
use Symfony\Component\PropertyInfo\Type;

/**
 * Creates properties's metadata using an extractor.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class ExtractorPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    use DeprecationMetadataTrait;
    private $extractor;
    private $decorated;

    public function __construct(PropertyExtractorInterface $extractor, PropertyMetadataFactoryInterface $decorated = null)
    {
        $this->extractor = $extractor;
        $this->decorated = $decorated;
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
            } catch (PropertyNotFoundException $propertyNotFoundException) {
                // Ignore not found exception from decorated factories
            }
        }

        if (
            !property_exists($resourceClass, $property) && !interface_exists($resourceClass) ||
            null === ($propertyMetadata = $this->extractor->getProperties()[$resourceClass][$property] ?? null)
        ) {
            return $this->handleNotFound($parentPropertyMetadata, $resourceClass, $property);
        }

        if ($parentPropertyMetadata) {
            return $this->update($parentPropertyMetadata, $propertyMetadata);
        }

        $apiProperty = new ApiProperty();

        foreach ($propertyMetadata as $key => $value) {
            if ('subresource' === $key) {
                trigger_deprecation('api-platform', '2.7', 'Using "subresource" is deprecated, declare another resource instead.');
                continue;
            }

            if ('builtinTypes' === $key && null !== $value) {
                $value = array_map(function (string $builtinType): Type {
                    return new Type($builtinType);
                }, $value);
            }

            $methodName = 'with'.ucfirst($key);

            if (method_exists($apiProperty, $methodName) && null !== $value) {
                $apiProperty = $apiProperty->{$methodName}($value);
            }
        }

        if (isset($propertyMetadata['attributes'])) {
            $apiProperty = $this->withDeprecatedAttributes($apiProperty, $propertyMetadata['attributes']);
        }

        return $apiProperty;
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

        throw new PropertyNotFoundException(sprintf('Property "%s" of the resource class "%s" not found.', $property, $resourceClass));
    }

    /**
     * Creates a new instance of metadata if the property is not already set.
     */
    private function update(ApiProperty $propertyMetadata, array $metadata): ApiProperty
    {
        $metadataAccessors = [
            'description' => 'get',
            'readable' => 'is',
            'writable' => 'is',
            'writableLink' => 'is',
            'readableLink' => 'is',
            'required' => 'is',
            'identifier' => 'is',
        ];

        foreach ($metadataAccessors as $metadataKey => $accessorPrefix) {
            if (null === $metadata[$metadataKey]) {
                continue;
            }

            $propertyMetadata = $propertyMetadata->{'with'.ucfirst($metadataKey)}($metadata[$metadataKey]);
        }

        if (isset($metadata['attributes'])) {
            $propertyMetadata = $this->withDeprecatedAttributes($propertyMetadata, $metadata['attributes']);
        }

        return $propertyMetadata;
    }
}
