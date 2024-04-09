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

use ApiPlatform\Doctrine\Odm\State\Options as ODMOptions;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\ParameterValidator\ParameterValidator;
use ApiPlatform\State\ProviderInterface;
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
    private ?ParameterValidator $queryParameterValidator = null;
    private ?ProviderInterface $provider = null;

    public function __construct($queryParameterValidator, ?ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null)
    {
        if ($queryParameterValidator instanceof ProviderInterface) {
            $this->provider = $queryParameterValidator;
        } else {
            trigger_deprecation('api-platform/core', '3.3', 'Use a "%s" as first argument in "%s" instead of "%s".', ProviderInterface::class, self::class, ParameterValidator::class);
            $this->queryParameterValidator = $queryParameterValidator;
        }

        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $operation = $this->initializeOperation($request);

        if (
            !$request->isMethodSafe()
            || !($attributes = RequestAttributesExtractor::extractAttributes($request))
            || 'GET' !== $request->getMethod()
            || $request->attributes->get('_api_platform_disable_listeners')
        ) {
            return;
        }

        if ('api_platform.symfony.main_controller' === $operation?->getController()) {
            return;
        }

        if (!($operation?->getQueryParameterValidationEnabled() ?? true) || !$operation instanceof HttpOperation) {
            return;
        }

        if ($this->provider instanceof ProviderInterface) {
            if (null === $operation->getQueryParameterValidationEnabled()) {
                $operation = $operation->withQueryParameterValidationEnabled('GET' === $request->getMethod());
            }

            $this->provider->provide($operation, $request->attributes->get('_api_uri_variables') ?? [], [
                'request' => $request,
                'uri_variables' => $request->attributes->get('_api_uri_variables') ?? [],
                'resource_class' => $operation->getClass(),
            ]);

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
