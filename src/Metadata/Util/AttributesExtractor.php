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

namespace ApiPlatform\Metadata\Util;

/**
 * Extracts data used by the library form given attributes.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 *
 * @internal
 */
final class AttributesExtractor
{
    private function __construct()
    {
    }

    /**
     * Extracts resource class, operation name and format request attributes. Returns an empty array if the request does
     * not contain required attributes.
     */
    public static function extractAttributes(array $attributes): array
    {
        $result = ['resource_class' => $attributes['_api_resource_class'] ?? null, 'has_composite_identifier' => $attributes['_api_has_composite_identifier'] ?? false];

        if (null === $result['resource_class']) {
            return [];
        }

        $hasRequestAttributeKey = false;
        if (isset($attributes['_api_operation_name'])) {
            $hasRequestAttributeKey = true;
            $result['operation_name'] = $attributes['_api_operation_name'];
        }
        if (isset($attributes['_api_operation'])) {
            $result['operation'] = $attributes['_api_operation'];
        }

        if ($previousObject = $attributes['previous_data'] ?? null) {
            $result['previous_data'] = $previousObject;
        }

        if (false === $hasRequestAttributeKey) {
            return [];
        }

        $result += [
            'receive' => (bool) ($attributes['_api_receive'] ?? true),
            'respond' => (bool) ($attributes['_api_respond'] ?? true),
            'persist' => (bool) ($attributes['_api_persist'] ?? true),
        ];

        return $result;
    }
}
