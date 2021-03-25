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

namespace ApiPlatform\Core\Serializer\Mapping\Loader;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Symfony\Component\Serializer\Mapping\AttributeMetadata;
use Symfony\Component\Serializer\Mapping\ClassMetadataInterface;
use Symfony\Component\Serializer\Mapping\Loader\LoaderInterface;

/**
 * Loader for resource from the properties attribute.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class ResourceMetadataLoader implements LoaderInterface
{
    private $resourceMetadataFactory;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function loadClassMetadata(ClassMetadataInterface $classMetadata): bool
    {
        try {
            $resourceMetadata = $this->resourceMetadataFactory->create($classMetadata->getName());
        } catch (ResourceClassNotFoundException $e) {
            return false;
        }

        if (null === $properties = $resourceMetadata->getAttribute('properties')) {
            return false;
        }

        $attributesMetadata = $classMetadata->getAttributesMetadata();

        foreach ($properties as $property => $value) {
            $propertyName = $value;
            $propertyMetadata = [];
            if (\is_array($value)) {
                $propertyName = $property;
                $propertyMetadata = $value;
            }

            if (!isset($attributesMetadata[$propertyName])) {
                $attributesMetadata[$propertyName] = new AttributeMetadata($propertyName);
                $classMetadata->addAttributeMetadata($attributesMetadata[$propertyName]);
            }

            if (isset($propertyMetadata['groups'])) {
                foreach ($propertyMetadata['groups'] as $group) {
                    $attributesMetadata[$propertyName]->addGroup($group);
                }
            }
        }

        return true;
    }
}
