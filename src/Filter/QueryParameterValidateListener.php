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
use ApiPlatform\Core\Event\EventInterface;
use ApiPlatform\Core\Exception\FilterValidationException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

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

    /**
     * @deprecated since version 2.5, to be removed in 3.0.
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        @trigger_error(sprintf('The method %s() is deprecated since 2.5 and will be removed in 3.0.', __METHOD__), E_USER_DEPRECATED);

        $this->handleEvent($event);
    }

    public function handleEvent(/*EventInterface */$event): void
    {
        if ($event instanceof EventInterface) {
            $request = $event->getContext()['request'];
        } elseif ($event instanceof GetResponseEvent) {
            @trigger_error(sprintf('Passing an instance of "%s" as argument of "%s" is deprecated since 2.5 and will not be possible anymore in 3.0. Pass an instance of "%s" instead.', GetResponseEvent::class, __METHOD__, EventInterface::class), E_USER_DEPRECATED);

            $request = $event->getRequest();
        } else {
            return;
        }
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
