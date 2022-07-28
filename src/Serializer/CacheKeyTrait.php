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

namespace ApiPlatform\Serializer;

trait CacheKeyTrait
{
    private function getCacheKey(?string $format, array $context): string|bool
    {
        foreach ($context[self::EXCLUDE_FROM_CACHE_KEY] ?? $this->defaultContext[self::EXCLUDE_FROM_CACHE_KEY] as $key) {
            unset($context[$key]);
        }
        unset($context[self::EXCLUDE_FROM_CACHE_KEY]);
        unset($context['cache_key']); // avoid artificially different keys

        try {
            return md5($format.serialize($context));
        } catch (\Exception) {
            // The context cannot be serialized, skip the cache
            return false;
        }
    }
}
