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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Validates query parameters depending on filter description.
 *
 * @author Julien Deniau <julien.deniau@gmail.com>
 */
final class QueryParameterValidateListener
{
    use FilterLocatorTrait;

    private $resourceMetadataFactory;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, ContainerInterface $filterLocator)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->setFilterLocator($filterLocator);
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (
            !$request->isMethodSafe()
            || !($attributes = RequestAttributesExtractor::extractAttributes($request))
            || !isset($attributes['collection_operation_name'])
            || 'get' !== ($operationName = $attributes['collection_operation_name'])
        ) {
            return;
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);
        $resourceFilters = $resourceMetadata->getCollectionOperationAttribute($operationName, 'filters', [], true);

        $errorList = [];
        foreach ($resourceFilters as $filterId) {
            if (!$filter = $this->getFilter($filterId)) {
                continue;
            }

            foreach ($filter->getDescription($attributes['resource_class']) as $name => $data) {
                $errorList = $this->checkRequired($errorList, $name, $data, $request);
            }
        }

        if ($errorList) {
            throw new FilterValidationException($errorList);
        }
    }

    private function checkRequired(array $errorList, string $name, array $data, Request $request): array
    {
        // filter is not required, the `checkRequired` method can not break
        if (!($data['required'] ?? false)) {
            return $errorList;
        }

        // if query param is not given, then break
        if (!$this->requestHasQueryParameter($request, $name)) {
            $errorList[] = sprintf('Query parameter "%s" is required', $name);

            return $errorList;
        }

        // if query param is empty and the configuration does not allow it
        if (!($data['swagger']['allowEmptyValue'] ?? false) && empty($this->requestGetQueryParameter($request, $name))) {
            $errorList[] = sprintf('Query parameter "%s" does not allow empty value', $name);
        }

        return $errorList;
    }

    /**
     * Test if request has required parameter.
     */
    private function requestHasQueryParameter(Request $request, string $name): bool
    {
        $matches = [];
        parse_str($name, $matches);
        if (!$matches) {
            return false;
        }

        $rootName = (string) (array_keys($matches)[0] ?? null);
        if (!$rootName) {
            return false;
        }

        if (\is_array($matches[$rootName])) {
            $keyName = array_keys($matches[$rootName])[0];

            $queryParameter = $request->query->get($rootName);

            return \is_array($queryParameter) && isset($queryParameter[$keyName]);
        }

        return $request->query->has($rootName);
    }

    /**
     * Test if required filter is valid. It validates array notation too like "required[bar]".
     */
    private function requestGetQueryParameter(Request $request, string $name)
    {
        $matches = [];
        parse_str($name, $matches);
        if (!$matches) {
            return null;
        }

        $rootName = array_keys($matches)[0] ?? '';
        if (!$rootName) {
            return null;
        }

        if (\is_array($matches[$rootName])) {
            $keyName = array_keys($matches[$rootName])[0];

            $queryParameter = $request->query->get($rootName);

            if (\is_array($queryParameter) && isset($queryParameter[$keyName])) {
                return $queryParameter[$keyName];
            }

            return null;
        }

        return $request->query->get($rootName);
    }
}
