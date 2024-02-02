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
use ApiPlatform\Doctrine\Odm\State\Options as ODMOptions;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\Util\OperationRequestInitiatorTrait;
use ApiPlatform\State\Util\RequestParser;
use ApiPlatform\Symfony\Util\RequestAttributesExtractor;
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

    public function __construct(private readonly QueryParameterValidator $queryParameterValidator, ?ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null)
    {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (
            !$request->isMethodSafe()
            || !($attributes = RequestAttributesExtractor::extractAttributes($request))
            || 'GET' !== $request->getMethod()
            || $request->attributes->get('_api_platform_disable_listeners')
        ) {
            return;
        }

        $operation = $this->initializeOperation($request);
        if ('api_platform.symfony.main_controller' === $operation?->getController()) {
            return;
        }

        if (!($operation?->getQueryParameterValidationEnabled() ?? true) || !$operation instanceof CollectionOperationInterface) {
            return;
        }

        $queryString = RequestParser::getQueryString($request);
        $queryParameters = $queryString ? RequestParser::parseRequestParams($queryString) : [];

        $class = $attributes['resource_class'];

        if ($options = $operation->getStateOptions()) {
            if ($options instanceof Options && $options->getEntityClass()) {
                $class = $options->getEntityClass();
            }

            if ($options instanceof ODMOptions && $options->getDocumentClass()) {
                $class = $options->getDocumentClass();
            }
        }

        $this->queryParameterValidator->validateFilters($class, $operation->getFilters() ?? [], $queryParameters);
    }
}
