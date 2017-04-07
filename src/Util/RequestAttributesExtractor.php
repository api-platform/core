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
    private function __construct()
    {
    }

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
        $result = ['resource_class' => $request->attributes->get('_api_resource_class')];

        if (null === $result['resource_class']) {
            throw new RuntimeException('The request attribute "_api_resource_class" must be defined.');
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
