<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\Mapping\Loader;

use Dunglas\JsonLdApiBundle\Mapping\ClassMetadata;

/**
 * Loader interface.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface LoaderInterface
{
    /**
     * Loads metadata for the given class.
     *
     * @param ClassMetadata $classMetadata
     * @param array|null    $normalizationGroups
     * @param array|null    $denormalizationGroups
     * @param array|null    $validationGroups
     *
     * @return bool
     */
    public function loadClassMetadata(
        ClassMetadata $classMetadata,
        array $normalizationGroups = null,
        array $denormalizationGroups = null,
        array $validationGroups = null
    );
}
