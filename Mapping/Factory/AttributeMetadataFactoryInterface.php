<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Mapping\Factory;

use Dunglas\ApiBundle\Mapping\AttributeMetadataInterface;
use Dunglas\ApiBundle\Mapping\ClassMetadataInterface;

/**
 * Attribute metadata factory. Used by loaders to create new instances of attribute metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface AttributeMetadataFactoryInterface
{
    /**
     *  If the method was called with the same class metadata and the same attribute name before,
     * the same metadata instance is returned.
     *
     *
     * @param ClassMetadataInterface $classMetadata
     * @param string                 $attributeName
     * @param array|null             $normalizationGroups
     * @param array|null             $denormalizationGroups
     *
     * @return AttributeMetadataInterface
     */
    public function getAttributeMetadataFor(
        ClassMetadataInterface $classMetadata,
        $attributeName,
        array $normalizationGroups = null,
        array $denormalizationGroups = null
    );
}
