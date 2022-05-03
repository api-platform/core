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

    public const OPERATION_ATTRIBUTE_KEY = 'query_parameter_validate';

    private $resourceMetadataFactory;

    private $queryParameterValidator;

    private $enabled;

    public function __construct(ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory, QueryParameterValidator $queryParameterValidator, bool $enabled = true)
    {
        $this->resourceMetadataCollectionFactory = $resourceMetadataFactory;
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

        if (!$operation || !($operation->getQueryParameterValidationEnabled() ?? true) || !$operation instanceof CollectionOperationInterface) {
            return;
        }

        $queryString = RequestParser::getQueryString($request);
        $queryParameters = $queryString ? RequestParser::parseRequestParams($queryString) : [];
        $this->queryParameterValidator->validateFilters($attributes['resource_class'], $operation->getFilters() ?? [], $queryParameters);
    }
}
