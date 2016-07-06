<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Api;

use ApiPlatform\Core\Exception\RuntimeException;
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
    const API_ATTRIBUTES = ['resource_class', 'format', 'mime_type'];

    /**
     * Extracts resource class, operation name and format request attributes. Throws an exception if the request does not
     * contain required attributes.
     *
     * @param Request $request
     *
     * @throws RuntimeException
     *
     * @return array
     */
    public static function extractAttributes(Request $request)
    {
        $result = [];

        foreach (self::API_ATTRIBUTES as $key) {
            $attributeKey = '_api_'.$key;
            $attributeValue = $request->attributes->get($attributeKey);

            if (null === $attributeValue) {
                throw new RuntimeException(sprintf('The request attribute "%s" must be defined.', $attributeKey));
            }

            $result[$key] = $attributeValue;
        }

        $collectionOperationName = $request->attributes->get('_api_collection_operation_name');
        $itemOperationName = $request->attributes->get('_api_item_operation_name');

        if ($collectionOperationName) {
            $result['collection_operation_name'] = $collectionOperationName;
        } elseif ($itemOperationName) {
            $result['item_operation_name'] = $itemOperationName;
        } else {
            throw new RuntimeException('One of the request attribute "_api_collection_operation_name" or "_api_item_operation_name" must be defined.');
        }

        return $result;
    }
}
