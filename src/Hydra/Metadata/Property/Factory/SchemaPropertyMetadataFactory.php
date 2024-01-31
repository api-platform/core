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

namespace ApiPlatform\Hydra\Metadata\Property\Factory;

use ApiPlatform\Exception\PropertyNotFoundException;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Util\ResourceClassInfoTrait;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

/**
 * Complete ApiProperty::schema.
 */
final class SchemaPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    use ResourceClassInfoTrait;

    public function __construct(ResourceClassResolverInterface $resourceClassResolver, private readonly ?PropertyMetadataFactoryInterface $decorated = null)
    {
        $this->resourceClassResolver = $resourceClassResolver;
    }

    public function create(string $resourceClass, string $property, array $options = []): ApiProperty
    {
        if (null === $this->decorated) {
            $propertyMetadata = new ApiProperty();
        } else {
            try {
                $propertyMetadata = $this->decorated->create($resourceClass, $property, $options);
            } catch (PropertyNotFoundException) {
                $propertyMetadata = new ApiProperty();
            }
        }

        $propertyJsonldContext = $propertyMetadata->getJsonldContext() ?? [];
        $types = $propertyMetadata->getBuiltinTypes() ?? [];

        // never override the key if it is already set
        if ([] === $types || [] !== $propertyJsonldContext) {
            return $propertyMetadata;
        }

        foreach ($types as $type) {
            $className = $type->getClassName();
            if (!$type->isCollection()
                && null !== $className
                && $this->resourceClassResolver->isResourceClass($className)
            ) {
                $propertyJsonldContext['owl:maxCardinality'] = 1;
            }
        }

        return $propertyMetadata->withJsonldContext($propertyJsonldContext);
    }
}
