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

use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Exception\RuntimeException;
use Dunglas\ApiBundle\Model\DataProviderInterface;
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
     * @param ResourceInterface     $resourceType
     * @param string|int            $id
     *
     * @return object
     *
     * @throws NotFoundHttpException
     */
    private function getItem(DataProviderInterface $dataProvider, ResourceInterface $resourceType, $id)
    {
        $data = $dataProvider->getItem($resourceType, $id, true);
        if (!$data) {
            throw new NotFoundHttpException('Not Found');
        }

        return $data;
    }

    /**
     * Extract resource type and format request attributes. Throws an exception if the request does not contain required
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
        $resourceType = $request->attributes->get('_resource_type');
        $format = $request->attributes->get('_api_format');
        if (!$resourceType || !$format) {
            throw new RuntimeException('The API is not properly configured.');
        }

        return [$resourceType, $format];
    }
}
