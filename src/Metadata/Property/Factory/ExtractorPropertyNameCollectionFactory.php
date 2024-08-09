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

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\Extractor\PropertyExtractorInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;

/**
 * Creates a property name collection using an extractor.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class ExtractorPropertyNameCollectionFactory implements PropertyNameCollectionFactoryInterface
{
    public function __construct(private readonly PropertyExtractorInterface $extractor, private readonly ?PropertyNameCollectionFactoryInterface $decorated = null)
    {
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    public function create(string $resourceClass, array $options = []): PropertyNameCollection
    {
        $propertyNames = [];
        $propertyNameCollection = null;

        if ($this->decorated) {
            try {
                $propertyNameCollection = $this->decorated->create($resourceClass, $options);
            } catch (ResourceClassNotFoundException) {
                // Ignore not found exceptions from decorated factory
            }

            foreach ($propertyNameCollection as $propertyName) {
                $propertyNames[$propertyName] = $propertyName;
            }
        }

        if (!class_exists($resourceClass) && !interface_exists($resourceClass)) {
            if (null !== $propertyNameCollection) {
                return $propertyNameCollection;
            }

            throw new ResourceClassNotFoundException(\sprintf('The resource class "%s" does not exist.', $resourceClass));
        }

        if ($properties = $this->extractor->getProperties()[$resourceClass] ?? false) {
            foreach ($properties as $propertyName => $property) {
                $propertyNames[$propertyName] = $propertyName;
            }
        }

        return new PropertyNameCollection(array_values($propertyNames));
    }
}
