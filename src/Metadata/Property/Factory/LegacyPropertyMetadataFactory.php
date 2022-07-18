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

use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface as LegacyPropertyMetadataFactoryInterface;
use ApiPlatform\Exception\PropertyNotFoundException;
use ApiPlatform\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\DeprecationMetadataTrait;

final class LegacyPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    use DeprecationMetadataTrait;

    private $legacyPropertyMetadataFactory;
    private $decorated;

    public function __construct(LegacyPropertyMetadataFactoryInterface $legacyPropertyMetadataFactory, PropertyMetadataFactoryInterface $decorated = null)
    {
        $this->legacyPropertyMetadataFactory = $legacyPropertyMetadataFactory;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = []): ApiProperty
    {
        if (null === $this->decorated) {
            $propertyMetadata = new ApiProperty();
        } else {
            try {
                $propertyMetadata = $this->decorated->create($resourceClass, $property, $options);
            } catch (PropertyNotFoundException $propertyNotFoundException) {
                $propertyMetadata = new ApiProperty();
            }
        }

        try {
            $legacyPropertyMetadata = $this->legacyPropertyMetadataFactory->create($resourceClass, $property, ['deprecate' => false] + $options);
        } catch (PropertyNotFoundException|ResourceClassNotFoundException $propertyNotFoundException) {
            return $propertyMetadata;
        }

        foreach (get_class_methods($legacyPropertyMetadata) as $method) {
            if (0 !== strpos($method, 'get') && 0 !== strpos($method, 'is')) {
                continue;
            }

            if ('getIri' === $method) {
                if (!$legacyPropertyMetadata->getIri()) {
                    continue;
                }

                $propertyMetadata = $propertyMetadata->withIris([$legacyPropertyMetadata->getIri()]);
                continue;
            }

            if ('getType' === $method) {
                if (!$legacyPropertyMetadata->getType()) {
                    continue;
                }

                $propertyMetadata = $propertyMetadata->withBuiltinTypes([$legacyPropertyMetadata->getType()]);
                continue;
            }

            $wither = str_replace(['get', 'is'], 'with', $method);

            if (method_exists($propertyMetadata, $wither) && null !== $legacyPropertyMetadata->{$method}() && null === $propertyMetadata->{$method}()) {
                $propertyMetadata = $propertyMetadata->{$wither}($legacyPropertyMetadata->{$method}());
            }
        }

        return $this->withDeprecatedAttributes($propertyMetadata, $legacyPropertyMetadata->getAttributes() ?? []);
    }
}
