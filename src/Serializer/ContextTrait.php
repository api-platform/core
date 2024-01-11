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

use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;


/**
 * Creates and manipulates the Serializer context.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
trait ContextTrait
{
    /**
     * Initializes the context.
     */
    private function initContext(string $resourceClass, array $context): array
    {
        return array_merge($context, [
            'api_sub_level' => true,
            'resource_class' => $resourceClass,
        ]);
    }

    /**
     * Exclude 'iri' from serializer cache key. Not doing this results in a cache explosion
     * when iterating big result sets because a unique iri generates unique cache keys in
     * Symfony Serializer's AbstractObjectNormalizer::getCacheKey()
     */
    private function excludeIriFromCacheKey(array $context): array
    {
        if (empty($context[AbstractObjectNormalizer::EXCLUDE_FROM_CACHE_KEY])) {
            $context[AbstractObjectNormalizer::EXCLUDE_FROM_CACHE_KEY] = ['iri'];
        } else {
            if (!in_array('iri', $context[AbstractObjectNormalizer::EXCLUDE_FROM_CACHE_KEY])) {
                $context[AbstractObjectNormalizer::EXCLUDE_FROM_CACHE_KEY][] = 'iri';
            }
        }

        return $context;
    }
}
