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

/**
 * Used to override Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer::getCacheKey which is private
 * We need the cache_key in JsonApi and Hal before it is computed in Symfony.
 *
 * @see https://github.com/symfony/symfony/blob/49b6ab853d81e941736a1af67845efa3401e7278/src/Symfony/Component/Serializer/Normalizer/AbstractObjectNormalizer.php#L723 which isn't protected
 */
trait CacheKeyTrait
{
    /**
     * @return string|bool
     */
    private function getCacheKey(?string $format, array $context)
    {
        foreach ($context[self::EXCLUDE_FROM_CACHE_KEY] ?? $this->defaultContext[self::EXCLUDE_FROM_CACHE_KEY] as $key) {
            unset($context[$key]);
        }
        unset($context[self::EXCLUDE_FROM_CACHE_KEY]);
        unset($context[self::OBJECT_TO_POPULATE]);
        unset($context['cache_key']); // avoid artificially different keys

        try {
            return hash('md5', $format.serialize([
                'context' => $context,
                'ignored' => $context[self::IGNORED_ATTRIBUTES] ?? $this->defaultContext[self::IGNORED_ATTRIBUTES],
            ]));
        } catch (\Exception $e) {
            // The context cannot be serialized, skip the cache
            return false;
        }
    }
}

if (!trait_exists(\ApiPlatform\Core\Serializer\CacheKeyTrait::class)) {
    class_alias(CacheKeyTrait::class, \ApiPlatform\Core\Serializer\CacheKeyTrait::class);
}
