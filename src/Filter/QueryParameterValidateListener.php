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
                if (!($data['required'] ?? false)) { // property is not required
                    continue;
                }

                if (!$this->isRequiredFilterValid($name, $request)) {
                    $errorList[] = sprintf('Query parameter "%s" is required', $name);
                }
            }
        }

        if ($errorList) {
            throw new FilterValidationException($errorList);
        }
    }

    /**
     * Test if required filter is valid. It validates array notation too like "required[bar]".
     */
    private function isRequiredFilterValid(string $name, Request $request): bool
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

        return null !== $request->query->get($rootName);
    }
}
