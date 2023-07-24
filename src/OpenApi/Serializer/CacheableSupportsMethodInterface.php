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

namespace ApiPlatform\OpenApi\Serializer;

use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface as BaseCacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Serializer;

if (method_exists(Serializer::class, 'getSupportedTypes')) {
    /**
     * Backward compatibility layer for getSupportedTypes().
     *
     * @internal
     *
     * @author Kévin Dunglas <kevin@dunglas.dev>
     *
     * @todo remove this interface when dropping support for Serializer < 6.3
     */
    interface CacheableSupportsMethodInterface
    {
        public function getSupportedTypes(?string $format): array;
    }
} else {
    /**
     * Backward compatibility layer for NormalizerInterface::getSupportedTypes().
     *
     * @internal
     *
     * @author Kévin Dunglas <kevin@dunglas.dev>
     *
     * @todo remove this interface when dropping support for Serializer < 6.3
     */
    interface CacheableSupportsMethodInterface extends BaseCacheableSupportsMethodInterface
    {
    }
}
