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

namespace ApiPlatform\Metadata\Tests\Extractor\Adapter;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
interface PropertyAdapterInterface
{
    /**
     * @param string                 $resourceClass Resource class
     * @param string                 $propertyName  Property name
     * @param \ReflectionParameter[] $parameters    Constructor parameters
     * @param array                  $fixtures      Some fixtures
     *
     * @return string[] A list of files to load in the extractor
     */
    public function __invoke(string $resourceClass, string $propertyName, array $parameters, array $fixtures): array;
}
