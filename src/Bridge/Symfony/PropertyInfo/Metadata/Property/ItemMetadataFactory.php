<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Symfony\PropertyInfo\Metadata\Property;

use ApiPlatform\Core\Exception\PropertyNotFoundException;
use ApiPlatform\Core\Metadata\Property\Factory\ItemMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\ItemMetadata;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;

/**
 * PropertyInfo metadata loader decorator.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemMetadataFactory implements ItemMetadataFactoryInterface
{
    private $propertyInfo;
    private $decorated;

    public function __construct(PropertyInfoExtractorInterface $propertyInfo, ItemMetadataFactoryInterface $decorated = null)
    {
        $this->propertyInfo = $propertyInfo;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $name, array $options = []) : ItemMetadata
    {
        if (null !== $this->decorated) {
            try {
                $itemMetadata = $this->decorated->create($resourceClass, $name, $options);
            } catch (PropertyNotFoundException $propertyNotFoundException) {
                $itemMetadata = new ItemMetadata();
            }
        }
        if (null === $itemMetadata->getType()) {
            $types = $this->propertyInfo->getTypes($resourceClass, $name, $options);
            if (isset($types[0])) {
                $itemMetadata = $itemMetadata->withType($types[0]);
            }
        }

        if (null === $itemMetadata->getDescription()) {
            $itemMetadata = $itemMetadata->withDescription($this->propertyInfo->getShortDescription($resourceClass, $name, $options));
        }

        if (null === $itemMetadata->isReadable()) {
            $itemMetadata = $itemMetadata->withReadable($this->propertyInfo->isReadable($resourceClass, $name, $options));
        }

        if (null === $itemMetadata->isWritable()) {
            $itemMetadata = $itemMetadata->withWritable($this->propertyInfo->isWritable($resourceClass, $name, $options));
        }

        return $itemMetadata;
    }
}
