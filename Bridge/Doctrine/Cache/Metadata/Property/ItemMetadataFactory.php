<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Bridge\Doctrine\Cache\Metadata\Property;

use Doctrine\Common\Cache\Cache;
use Dunglas\ApiBundle\Metadata\Property\Factory\ItemMetadataFactoryInterface;
use Dunglas\ApiBundle\Metadata\Property\ItemMetadata;

/**
 * Property metadata loader cache decorator.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemMetadataFactory implements ItemMetadataFactoryInterface
{
    const KEY_PATTERN = 'p_%s_%s_%s';

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var ItemMetadataFactoryInterface
     */
    private $decorated;

    public function __construct(Cache $cache, ItemMetadataFactoryInterface $decorated)
    {
        $this->cache = $cache;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = []) : ItemMetadata
    {
        //
        $key = sprintf(self::KEY_PATTERN, $property, serialize($options));

        if ($this->cache->contains($key)) {
            return $this->cache->fetch($key);
        }

        $itemMetadata = $this->decorated->getMetadata($resourceClass, $property, $options);
        $this->cache->save($key, $itemMetadata);

        return $itemMetadata;
    }
}
