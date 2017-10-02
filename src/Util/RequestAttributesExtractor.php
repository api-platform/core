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

namespace ApiPlatform\Core\Util;

use ApiPlatform\Core\Api\OperationType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Extracts data used by the library form a Request instance.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @internal
 */
final class RequestAttributesExtractor
{
    private function __construct()
    {
    }

    /**
     * Extracts resource class, operation name and format request attributes. Returns an empty array if the request does
     * not contain required attributes.
     *
     * @param Request $request
     *
     * @return array
     */
    public static function extractAttributes(Request $request)
    {
        $result = ['resource_class' => $request->attributes->get('_api_resource_class')];

        if ($subresourceContext = $request->attributes->get('_api_subresource_context')) {
            $result['subresource_context'] = $subresourceContext;
        }

        if (null === $result['resource_class']) {
            return [];
        }

        $hasRequestAttributeKey = false;
        foreach (OperationType::TYPES as $operationType) {
            $attribute = "_api_{$operationType}_operation_name";
            if ($request->attributes->has($attribute)) {
                $result["{$operationType}_operation_name"] = $request->attributes->get($attribute);
                $hasRequestAttributeKey = true;
                break;
            }
        }

        if (false === $hasRequestAttributeKey) {
            return [];
        }

        if (null === $apiRequest = $request->attributes->get('_api_receive')) {
            $result['receive'] = true;
        } else {
            $result['receive'] = (bool) $apiRequest;
        }

        return $result;
    }
}
