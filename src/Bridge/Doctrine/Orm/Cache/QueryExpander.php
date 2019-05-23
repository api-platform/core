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

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Cache;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Doctrine\ORM\AbstractQuery;

/**
 * Class QueryExpander.
 *
 * Expands Query settings with Second Level Cache and Result Cache settings from attributes.
 *
 * @author st-it <33101537+st-it@users.noreply.github.com>
 */
class QueryExpander
{
    public const CACHEABLE_ATTR = 'cacheable';
    public const CACHE_HINT_ATTR = 'cache_hint';
    public const CACHE_MODE_ATTR = 'cache_mode';
    public const USE_RESULT_CACHE_ATTR = 'use_result_cache';

    private $resourceMetadataFactory;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * @throws ResourceClassNotFoundException
     */
    public function expand(string $resourceClass, AbstractQuery $query)
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $this->processCacheable($resourceMetadata, $query);
        $this->processCacheHint($resourceMetadata, $query);
        $this->processCacheMode($resourceMetadata, $query);
        $this->processResultCache($resourceMetadata, $query);
    }

    private function processCacheable(ResourceMetadata $resourceMetadata, AbstractQuery $query)
    {
        if (false !== $resourceMetadata->getAttribute(self::CACHEABLE_ATTR, false)) {
            $query->setCacheable(true);
        }
    }

    /**
     * @throws RuntimeException
     */
    private function processCacheHint(ResourceMetadata $resourceMetadata, AbstractQuery $query)
    {
        if (null !== $cacheHint = $resourceMetadata->getAttribute(self::CACHE_HINT_ATTR, null)) {
            if (!\is_array($cacheHint)) {
                throw new RuntimeException(sprintf('Attribute value %s should be an array', self::CACHE_HINT_ATTR));
            }
            foreach ($cacheHint as $name => $value) {
                $query->setHint($name, $value);
            }
        }
    }

    private function processCacheMode(ResourceMetadata $resourceMetadata, AbstractQuery $query)
    {
        if (null !== $cacheMode = $resourceMetadata->getAttribute(self::CACHE_MODE_ATTR, null)) {
            $query->setCacheMode($cacheMode);
        }
    }

    /**
     * @throws RuntimeException
     */
    private function processResultCache(ResourceMetadata $resourceMetadata, AbstractQuery $query)
    {
        if (null !== $useResultCache = $resourceMetadata->getAttribute(self::USE_RESULT_CACHE_ATTR, null)) {
            if (!\is_array($useResultCache)) {
                throw new RuntimeException(sprintf('Attribute value %s should be an array', self::USE_RESULT_CACHE_ATTR));
            }
            if (empty($useResultCache) || 3 < \count($useResultCache)) {
                throw new RuntimeException(sprintf('Attribute %s should at least contain one item for use. Other options are lifetime and and result cache id', self::USE_RESULT_CACHE_ATTR));
            }
            $query->useResultCache(...$useResultCache);
        }
    }
}
