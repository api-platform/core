<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Bridge\Symfony\PropertyInfo\Metadata\Property;

use Dunglas\ApiBundle\Metadata\Property\CollectionMetadata;
use Dunglas\ApiBundle\Metadata\Property\Factory\CollectionMetadataFactoryInterface;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;

/**
 * PropertyInfo collection loader.
 *
 * This is not a decorator on purpose because it should always have the top priority.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class CollectionMetadataFactory implements CollectionMetadataFactoryInterface
{
    /**
     * @var PropertyInfoExtractorInterface
     */
    private $propertyInfo;

    public function __construct(PropertyInfoExtractorInterface $propertyInfo)
    {
        $this->propertyInfo = $propertyInfo;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, array $options = []) : CollectionMetadata
    {
        return new CollectionMetadata($this->propertyInfo->getProperties($resourceClass, $options));
    }
}
