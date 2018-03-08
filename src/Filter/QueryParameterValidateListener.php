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

namespace ApiPlatform\Core\Filter;

use ApiPlatform\Core\Api\FilterLocatorTrait;
use ApiPlatform\Core\Exception\FilterValidationException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Validates query parameters depending on filter description.
 *
 * @author Julien Deniau <julien.deniau@gmail.com>
 */
class QueryParameterValidateListener
{
    use FilterLocatorTrait;

    private $resourceMetadataFactory;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, ContainerInterface $filterLocator)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->setFilterLocator($filterLocator);
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (
            !$request->isMethodSafe(false)
            || !($attributes = RequestAttributesExtractor::extractAttributes($request))
            || !isset($attributes['collection_operation_name'])
            || 'get' !== ($operationName = $attributes['collection_operation_name'])
        ) {
            return;
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);
        $resourceFilters = $resourceMetadata->getCollectionOperationAttribute($operationName, 'filters', [], true);

        foreach ($resourceFilters as $filterId) {
            if (!$filter = $this->getFilter($filterId)) {
                continue;
            }

            foreach ($filter->getDescription($attributes['resource_class']) as $name => $data) {
                $errorList = [];

                if (!($data['required'] ?? false)) { // property is not required
                    continue;
                }

                if (false !== strpos($name, '[')) { // array notation of filter
                    if (!$this->isArrayNotationFilterValid($name, $request)) {
                        $errorList[] = sprintf('Query parameter "%s" is required', $name);
                    }
                } elseif (null === $request->query->get($name)) {
                    $errorList[] = sprintf('Query parameter "%s" is required', $name);
                }

                if ($errorList) {
                    throw new FilterValidationException($errorList);
                }
            }
        }
    }

    private function isArrayNotationFilterValid($name, $request): bool
    {
        $matches = [];
        preg_match('/([^[]+)\[(.*)\]/', $name, $matches);
        list(, $rootName, $keyName) = $matches;
        $keyName = $keyName ?: 0; // array without index should test the first key
        $queryParameter = $request->query->get($rootName);

        return is_array($queryParameter) && isset($queryParameter[$keyName]);
    }
}
