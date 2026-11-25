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

namespace ApiPlatform\Symfony\Bundle\CacheWarmer;

use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\SubtypeResolverInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Names the subtypes of every resource on cache:warmup, so a name clash fails the warmup instead of a request.
 *
 * @internal
 */
final class SubtypeCacheWarmer implements CacheWarmerInterface
{
    public function __construct(private readonly SubtypeResolverInterface $subtypeResolver, private readonly ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory)
    {
    }

    /**
     * @return string[]
     */
    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            foreach ($this->subtypeResolver->getSubtypes($resourceClass) as $subtype) {
                $this->subtypeResolver->getTypes($subtype);
            }
        }

        return [];
    }

    public function isOptional(): bool
    {
        return true;
    }
}
