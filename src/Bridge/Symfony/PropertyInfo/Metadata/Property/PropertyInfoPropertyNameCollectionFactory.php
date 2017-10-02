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

namespace ApiPlatform\Core\Bridge\Symfony\PropertyInfo\Metadata\Property;

use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;

/**
 * PropertyInfo collection loader.
 *
 * This is not a decorator on purpose because it should always have the top priority.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class PropertyInfoPropertyNameCollectionFactory implements PropertyNameCollectionFactoryInterface
{
    private $propertyInfo;

    public function __construct(PropertyInfoExtractorInterface $propertyInfo)
    {
        $this->propertyInfo = $propertyInfo;
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function create(string $resourceClass, array $options = []): PropertyNameCollection
    {
        $properties = $this->propertyInfo->getProperties($resourceClass, $options);
        if (null === $properties) {
            throw new RuntimeException(sprintf('There is no PropertyInfo extractor supporting the class "%s".', $resourceClass));
        }

        return new PropertyNameCollection($properties);
    }
}
