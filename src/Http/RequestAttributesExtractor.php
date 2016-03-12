<?php

/*
 *  This file is part of the API Platform project.
 *
 *  (c) Kévin Dunglas <dunglas@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Http;

use ApiPlatform\Core\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
final class RequestAttributesExtractor implements RequestAttributesExtractorInterface
{
    /**
     * {@inheritdoc}
     */
    public function extract(Request $request)
    {
        $resourceClass = $this->retrieveAttribute($request, '_resource_class');

        try {
            $collectionOperation = $this->retrieveAttribute($request, '_collection_operation_name');
            $itemOperation = null;
        } catch (RuntimeException $exception) {
            try {
                $collectionOperation = null;
                $itemOperation = $this->retrieveAttribute($request, '_item_operation_name');
            } catch (RuntimeException $exception) {
                throw new RuntimeException('One of the request attribute "_item_operation_name" or "_collection_operation_name" must be defined.');
            }
        }

        $format = $this->retrieveAttribute($request, '_api_format');

        return new AttributesBag($resourceClass, $collectionOperation, $itemOperation, $format);
    }

    /**
     * @param Request $request
     * @param string  $attributeName
     *
     * @throws RuntimeException
     *
     * @return string
     */
    private function retrieveAttribute(Request $request, string $attributeName)
    {
        $attribute = $request->attributes->get('_resource_class');

        if (null === $attribute) {
            throw new RuntimeException(sprintf('The request attribute "%s" must be defined.', $attributeName));
        }

        if (!is_string($attribute)) {
            throw new RuntimeException(sprintf('The request attribute "%s" must be a string.', $attributeName));
        }

        return $attribute;
    }
}
