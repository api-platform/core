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

use ApiPlatform\Core\Metadata\Extractor\ExtractorInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\SubresourceMetadata;
use ApiPlatform\Exception\PropertyNotFoundException;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\DeprecationMetadataTrait;
use Symfony\Component\PropertyInfo\Type;

/**
 * Creates properties's metadata using an extractor.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ExtractorPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    use DeprecationMetadataTrait;
    private $extractor;
    private $decorated;

    public function __construct(ExtractorInterface $extractor, PropertyMetadataFactoryInterface $decorated = null)
    {
        $this->extractor = $extractor;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = [])
    {
        $parentPropertyMetadata = null;
        if ($this->decorated) {
            try {
                $parentPropertyMetadata = $this->decorated->create($resourceClass, $property, $options);
            } catch (PropertyNotFoundException $propertyNotFoundException) {
                // Ignore not found exception from decorated factories
            }
        }

        $isInterface = interface_exists($resourceClass);

        if (
            !property_exists($resourceClass, $property) && !$isInterface ||
            null === ($propertyMetadata = $this->extractor->getResources()[$resourceClass]['properties'][$property] ?? null)
        ) {
            return $this->handleNotFound($parentPropertyMetadata, $resourceClass, $property);
        }

        if ($parentPropertyMetadata) {
            return $this->update($parentPropertyMetadata, $propertyMetadata);
        }

        $apiProperty = new ApiProperty();

        foreach ($propertyMetadata as $key => $value) {
            if ('subresource' === $key) {
                continue;
            }
            $methodName = 'with'.ucfirst($key);

            if (method_exists($apiProperty, $methodName) && null !== $value) {
                $apiProperty = $apiProperty->{$methodName}($value);
            }
        }

        $metadata = $this->withDeprecatedAttributes($apiProperty, $propertyMetadata['attributes']);

        if (isset($propertyMetadata['iri'])) {
            trigger_deprecation('api-platform', '2.7', 'Using "iri" is deprecated, use "types" instead.');
            $metadata = $metadata->withTypes([$propertyMetadata['iri']]);
        }

        if ($subresource = $this->createSubresourceMetadata($propertyMetadata['subresource'], $metadata)) {
            return $metadata->withSubresource($subresource);
        }

        return $metadata;
    }

    /**
     * Returns the metadata from the decorated factory if available or throws an exception.
     *
     * @param ApiProperty|PropertyMetadata|null $parentPropertyMetadata
     *
     * @throws PropertyNotFoundException
     *
     * @return ApiProperty|PropertyMetadata
     */
    private function handleNotFound($parentPropertyMetadata, string $resourceClass, string $property)
    {
        if ($parentPropertyMetadata) {
            return $parentPropertyMetadata;
        }

        throw new PropertyNotFoundException(sprintf('Property "%s" of the resource class "%s" not found.', $property, $resourceClass));
    }

    /**
     * Creates a new instance of metadata if the property is not already set.
     *
     * @param ApiProperty|PropertyMetadata|null $propertyMetadata
     *
     * @return ApiProperty|PropertyMetadata
     */
    private function update($propertyMetadata, array $metadata)
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

        if ($propertyMetadata instanceof ApiProperty) {
            $propertyMetadata = $this->withDeprecatedAttributes($propertyMetadata, $metadata['attributes']);

            if (isset($metadata['iri'])) {
                trigger_deprecation('api-platform', '2.7', 'Using "iri" is deprecated, use "types" instead.');
                $propertyMetadata = $propertyMetadata->withTypes([$metadata['iri']]);
            }
        } else {
            $propertyMetadata = $propertyMetadata->withIri($metadata['iri'])->withAttributes($metadata['attributes']);
        }

        if ($propertyMetadata->hasSubresource()) {
            return $propertyMetadata;
        }

        if ($subresource = $this->createSubresourceMetadata($metadata['subresource'], $propertyMetadata)) {
            return $propertyMetadata->withSubresource($subresource);
        }

        return $propertyMetadata;
    }

    /**
     * Creates a SubresourceMetadata.
     *
     * @param bool|array|null              $subresource      the subresource metadata coming from XML or YAML
     * @param ApiProperty|PropertyMetadata $propertyMetadata the current property metadata
     */
    private function createSubresourceMetadata($subresource, $propertyMetadata): ?SubresourceMetadata
    {
        if (!$subresource) {
            return null;
        }

        $type = $propertyMetadata instanceof PropertyMetadata ? $propertyMetadata->getType() : $propertyMetadata->getBuiltinTypes()[0] ?? null;
        $maxDepth = \is_array($subresource) ? $subresource['maxDepth'] ?? null : null;

        if (null !== $type) {
            $isCollection = $type->isCollection();
            if (
                $isCollection &&
                $collectionValueType = method_exists(Type::class, 'getCollectionValueTypes') ? ($type->getCollectionValueTypes()[0] ?? null) : $type->getCollectionValueType()
            ) {
                $resourceClass = $collectionValueType->getClassName();
            } else {
                $resourceClass = $type->getClassName();
            }
        } elseif (\is_array($subresource) && isset($subresource['resourceClass'])) {
            $resourceClass = $subresource['resourceClass'];
            $isCollection = $subresource['collection'] ?? true;
        } else {
            return null;
        }

        return new SubresourceMetadata($resourceClass, $isCollection, $maxDepth);
    }
}
