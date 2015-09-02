<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Bridge\Doctrine\Cache\Metadata\Resource;

use Doctrine\Common\Cache\Cache;
use Dunglas\ApiBundle\Metadata\Resource\Factory\ItemMetadataFactoryInterface;
use Dunglas\ApiBundle\Metadata\Resource\ItemMetadata;

/**
 * Cache decorator.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemMetadataFactory implements ItemMetadataFactoryInterface
{
    const KEY_PATTERN = 'r_%s';

    /**
     * @var ItemMetadataFactoryInterface
     */
    private $decorated;

    /**
     * @var Cache
     */
    private $cache;

    public function __construct(Cache $cache, ItemMetadataFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass) : ItemMetadata
    {
        $key = sprintf(self::KEY_PATTERN, $resourceClass);

        if ($this->cache->contains($key)) {
            return $this->cache->fetch($key);
        }

        $metadata = $this->decorated->create($resourceClass);
        $this->cache->save($key, $metadata);

        return $metadata;
    }
}
