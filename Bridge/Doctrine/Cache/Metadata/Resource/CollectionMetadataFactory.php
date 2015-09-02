<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Bridge\Doctrine\Cache\Mapping\Resource;

use Doctrine\Common\Cache\Cache;
use Dunglas\ApiBundle\Metadata\Resource\CollectionMetadata;
use Dunglas\ApiBundle\Metadata\Resource\Factory\CollectionMetadataFactoryInterface;

/**
 * Cache decorator.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class CollectionMetadataFactory implements CollectionMetadataFactoryInterface
{
    const KEY = 'rc';

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var CollectionMetadataFactoryInterface
     */
    private $decorated;

    public function __construct(Cache $cache, CollectionMetadataFactoryInterface $decorated)
    {
        $this->cache = $cache;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create() : CollectionMetadata
    {
        if ($this->cache->contains(self::KEY)) {
            return $this->cache->fetch(self::KEY);
        }

        $collectionMetadata = $this->decorated->create();
        $this->cache->save(self::KEY, $collectionMetadata);

        return $collectionMetadata;
    }
}
