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

use Dunglas\ApiBundle\Exception\InvalidArgumentException;
use Dunglas\ApiBundle\Mapping\ClassMetadataInterface;

/**
 * Class metadata factory. Use loaders to populates structures.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface ClassMetadataFactoryInterface
{
    /**
     * If the method was called with the same class name (or an object of that
     * class) before, the same metadata instance is returned.
     *
     * If the factory was configured with a cache, this method will first look
     * for an existing metadata instance in the cache. If an existing instance
     * is found, it will be returned without further ado.
     *
     * Otherwise, a new metadata instance is created. If the factory was
     * configured with a loader, the metadata is passed to the
     * {@link LoaderInterface::loadClassMetadata()} method for further
     * configuration. At last, the new object is returned.
     *
     * @param string|object $value
     * @param string[]|null $normalizationGroups
     * @param string[]|null $denormalizationGroups
     * @param string[]|null $validationGroups
     *
     * @return ClassMetadataInterface
     *
     * @throws InvalidArgumentException
     */
    public function getMetadataFor($value, array $normalizationGroups = null, array $denormalizationGroups = null, array $validationGroups = null);

    /**
     * Checks if class has metadata.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function hasMetadataFor($value);
}
