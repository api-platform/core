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
 * Interface for collecting cache tags during normalization.
 *
 * @author Urban Suppiger <urban@suppiger.net>
 */
interface TagCollectorInterface
{
    /**
     * Collect cache tags for cache invalidation.
     *
     * @param array<string, mixed>&array{iri?: string, data?: mixed, object?: mixed, property_metadata?: \ApiPlatform\Metadata\ApiProperty, api_attribute?: string, resources?: array<string, string>, format?: string, operation?: \ApiPlatform\Metadata\Operation} $context
     */
    public function collect(array $context = []): void;
}
