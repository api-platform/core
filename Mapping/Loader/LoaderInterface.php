<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Mapping\Loader;

use Dunglas\ApiBundle\Mapping\ClassMetadata;

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
     * @param string[]|null $normalizationGroups
     * @param string[]|null $denormalizationGroups
     * @param string[]|null $validationGroups
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
