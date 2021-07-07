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

namespace ApiPlatform\Core\EventListener;

use ApiPlatform\Core\Filter\QueryParameterValidator;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ToggleableOperationAttributeTrait;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use ApiPlatform\Core\Util\RequestParser;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Validates query parameters depending on filter description.
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
final class QueryParameterValidateListener
{
    use ToggleableOperationAttributeTrait;

    public const OPERATION_ATTRIBUTE_KEY = 'query_parameter_validate';

    private $resourceMetadataFactory;

    private $queryParameterValidator;

    private $enabled;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, QueryParameterValidator $queryParameterValidator, bool $enabled = true)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->queryParameterValidator = $queryParameterValidator;
        $this->enabled = $enabled;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        if (
            !$request->isMethodSafe()
            || !($attributes = RequestAttributesExtractor::extractAttributes($request))
            || !isset($attributes['collection_operation_name'])
            || !($operationName = $attributes['collection_operation_name'])
            || 'GET' !== $request->getMethod()
            || $this->isOperationAttributeDisabled($attributes, self::OPERATION_ATTRIBUTE_KEY, !$this->enabled)
        ) {
            return;
        }
        $queryString = RequestParser::getQueryString($request);
        $queryParameters = $queryString ? RequestParser::parseRequestParams($queryString) : [];

        $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);
        $resourceFilters = $resourceMetadata->getCollectionOperationAttribute($operationName, 'filters', [], true);

        $this->queryParameterValidator->validateFilters($attributes['resource_class'], $resourceFilters, $queryParameters);
    }
}
