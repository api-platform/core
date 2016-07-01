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
 */
final class RequestAttributesExtractor
{
    /**
     * Extracts resource class, operation name and format request attributes. Throws an exception if the request does not contain required
     * attributes.
     *
     * @param Request $request
     *
     * @throws RuntimeException
     *
     * @return array
     */
    public static function extractAttributes(Request $request)
    {
        $resourceClass = $request->attributes->get('_resource_class');

        if (!$resourceClass) {
            throw new RuntimeException('The request attribute "_resource_class" must be defined.');
        }

        $collectionOperation = $request->attributes->get('_collection_operation_name');
        $itemOperation = $request->attributes->get('_item_operation_name');

        if (!$itemOperation && !$collectionOperation) {
            throw new RuntimeException('One of the request attribute "_item_operation_name" or "_collection_operation_name" must be defined.');
        }

        $format = $request->attributes->get('_api_format');
        if (!$format) {
            throw new RuntimeException('The request attribute "_api_format" must be defined.');
        }

        return [$resourceClass, $collectionOperation, $itemOperation, $format];
    }
}
