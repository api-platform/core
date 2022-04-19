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

namespace ApiPlatform\Symfony\EventListener;

use ApiPlatform\Api\QueryParameterValidator\QueryParameterValidator;
use ApiPlatform\Core\Filter\QueryParameterValidator as LegacyQueryParameterValidator;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ToggleableOperationAttributeTrait;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Util\RequestAttributesExtractor;
use ApiPlatform\Util\RequestParser;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Validates query parameters depending on filter description.
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
final class QueryParameterValidateListener
{
    use OperationRequestInitiatorTrait;
    use ToggleableOperationAttributeTrait;

    public const OPERATION_ATTRIBUTE_KEY = 'query_parameter_validate';

    private $resourceMetadataFactory;

    private $queryParameterValidator;

    private $enabled;

    /**
     * @param ResourceMetadataCollectionFactoryInterface|ResourceMetadataFactoryInterface $resourceMetadataFactory
     * @param QueryParameterValidator|LegacyQueryParameterValidator                       $queryParameterValidator
     */
    public function __construct($resourceMetadataFactory, $queryParameterValidator, bool $enabled = true)
    {
        if (!$resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use "%s" instead of "%s".', ResourceMetadataCollectionFactoryInterface::class, ResourceMetadataFactoryInterface::class));
        } else {
            $this->resourceMetadataCollectionFactory = $resourceMetadataFactory;
        }

        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->queryParameterValidator = $queryParameterValidator;
        $this->enabled = $enabled;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        $operation = $this->initializeOperation($request);

        if (
            !$request->isMethodSafe()
            || !($attributes = RequestAttributesExtractor::extractAttributes($request))
            || 'GET' !== $request->getMethod()
        ) {
            return;
        }

        if ($this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface &&
            (!$operation || !($operation->getQueryParameterValidationEnabled() ?? true) || !$operation instanceof CollectionOperationInterface)
        ) {
            return;
        }

        // TODO: remove in 3.0
        $operationName = $attributes['collection_operation_name'] ?? null;
        if (!$this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface &&
            (
                null === $operationName
                || $this->isOperationAttributeDisabled($attributes, self::OPERATION_ATTRIBUTE_KEY, !$this->enabled)
            )
        ) {
            return;
        }

        $queryString = RequestParser::getQueryString($request);
        $queryParameters = $queryString ? RequestParser::parseRequestParams($queryString) : [];
        $resourceFilters = [];
        if ($this->resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
            $resourceFilters = $this->resourceMetadataFactory->create($attributes['resource_class'])->getCollectionOperationAttribute($operationName, 'filters', [], true);
        } elseif ($operation) {
            $resourceFilters = $operation->getFilters() ?? [];
        }
        $this->queryParameterValidator->validateFilters($attributes['resource_class'], $resourceFilters, $queryParameters);
    }
}

class_alias(QueryParameterValidateListener::class, \ApiPlatform\Core\EventListener\QueryParameterValidateListener::class);
