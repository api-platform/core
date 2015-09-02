<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Action;

use Dunglas\ApiBundle\Exception\RuntimeException;
use Dunglas\ApiBundle\Api\DataProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Checks if the request is properly configured.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
trait ActionUtilTrait
{
    /**
     * Gets an item using the data provider. Throws a 404 error if not found.
     *
     * @param DataProviderInterface $dataProvider
     * @param string                $resourceClass
     * @param string                $operationName
     * @param string|int            $id
     *
     * @return object
     *
     * @throws NotFoundHttpException
     */
    private function getItem(DataProviderInterface $dataProvider, string $resourceClass, string $operationName, $id)
    {
        $data = $dataProvider->getItem($resourceClass, $id, $operationName, true);
        if (!$data) {
            throw new NotFoundHttpException('Not Found');
        }

        return $data;
    }

    /**
     * Extract resource class, operation name and format request attributes. Throws an exception if the request does not contain required
     * attributes.
     *
     * @param Request $request
     *
     * @return array
     *
     * @throws RuntimeException
     */
    private function extractAttributes(Request $request)
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
