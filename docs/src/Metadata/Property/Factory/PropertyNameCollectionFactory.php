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

namespace ApiPlatform\Playground\Metadata\Property\Factory;

use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use Psr\Cache\CacheItemPoolInterface;

class PropertyNameCollectionFactory implements PropertyNameCollectionFactoryInterface
{
    public function __construct(private CacheItemPoolInterface $cacheItemPool, private PropertyNameCollectionFactoryInterface $decorated)
    {
    }

    public function create(string $resourceClass, array $options = []): PropertyNameCollection
    {
        return $this->decorated->create($resourceClass, $options);
    }
}
