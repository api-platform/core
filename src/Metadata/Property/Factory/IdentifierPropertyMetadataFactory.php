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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Exception\PropertyNotFoundException;
use ApiPlatform\Metadata\ResourceClassResolverInterface;

final class IdentifierPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    public function __construct(private readonly ResourceClassResolverInterface $resourceClassResolver, private readonly ?PropertyMetadataFactoryInterface $decorated = null)
    {
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

        if (!$this->resourceClassResolver->isResourceClass($resourceClass)) {
            return $propertyMetadata;
        }

        if ('id' === $property && null === $propertyMetadata->isIdentifier()) {
            return $propertyMetadata->withIdentifier(true);
        }

        return $propertyMetadata;
    }
}
