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

use ApiPlatform\Api\UriVariablesConverterInterface as LegacyUriVariablesConverterInterface;
use ApiPlatform\Exception\InvalidIdentifierException;
use ApiPlatform\Exception\InvalidUriVariableException;
use ApiPlatform\Metadata\Error;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\UriVariablesConverterInterface;
use ApiPlatform\Metadata\Util\CloneTrait;
use ApiPlatform\Serializer\SerializerContextBuilderInterface as LegacySerializerContextBuilderInterface;
use ApiPlatform\State\CallableProvider;
use ApiPlatform\State\Exception\ProviderNotFoundException;
use ApiPlatform\State\Provider\ReadProvider;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\SerializerContextBuilderInterface;
use ApiPlatform\State\UriVariablesResolverTrait;
use ApiPlatform\State\Util\OperationRequestInitiatorTrait;
use ApiPlatform\State\Util\RequestParser;
use ApiPlatform\Symfony\Util\RequestAttributesExtractor;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Retrieves data from the applicable data provider and sets it as a request parameter called data.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ReadListener
{
    use CloneTrait;
    use OperationRequestInitiatorTrait;
    use UriVariablesResolverTrait;

    public function __construct(
        private readonly ProviderInterface $provider,
        ?ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null,
        private readonly LegacySerializerContextBuilderInterface|SerializerContextBuilderInterface|null $serializerContextBuilder = null,
        LegacyUriVariablesConverterInterface|UriVariablesConverterInterface|null $uriVariablesConverter = null,
    ) {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->uriVariablesConverter = $uriVariablesConverter;

        if ($provider instanceof CallableProvider) {
            trigger_deprecation('api-platform/core', '3.3', 'Use a "%s" as first argument in "%s" instead of "%s".', ReadProvider::class, self::class, $provider::class);
        }
    }

    /**
     * Calls the data provider and sets the data attribute.
     *
     * @throws NotFoundHttpException
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!($attributes = RequestAttributesExtractor::extractAttributes($request)) || !$attributes['receive']) {
            return;
        }

        $operation = $this->initializeOperation($request);

        if ($operation && !$this->provider instanceof CallableProvider) {
            if (null === $operation->canRead()) {
                $operation = $operation->withRead($operation->getUriVariables() || $request->isMethodSafe());
            }

            $uriVariables = [];
            if (!$operation instanceof Error && $operation instanceof HttpOperation) {
                try {
                    $uriVariables = $this->getOperationUriVariables($operation, $request->attributes->all(), $operation->getClass());
                } catch (InvalidIdentifierException|InvalidUriVariableException $e) {
                    if ($operation->canRead()) {
                        throw new NotFoundHttpException('Invalid identifier value or configuration.', $e);
                    }
                }
            }

            $request->attributes->set('_api_uri_variables', $uriVariables);
            $this->provider->provide($operation, $uriVariables, [
                'request' => $request,
                'uri_variables' => $uriVariables,
                'resource_class' => $operation->getClass(),
            ]);

            return;
        }

        if ('api_platform.symfony.main_controller' === $operation?->getController() || $request->attributes->get('_api_platform_disable_listeners')) {
            return;
        }

        if (!$operation || !($operation->canRead() ?? true) || (!$operation->getUriVariables() && !$request->isMethodSafe())) {
            return;
        }

        $context = ['operation' => $operation];

        if (null === $filters = $request->attributes->get('_api_filters')) {
            $queryString = RequestParser::getQueryString($request);
            $filters = $queryString ? RequestParser::parseRequestParams($queryString) : null;
        }

        if ($filters) {
            $context['filters'] = $filters;
        }

        if ($this->serializerContextBuilder) {
            // Builtin data providers are able to use the serialization context to automatically add join clauses
            $context += $normalizationContext = $this->serializerContextBuilder->createFromRequest($request, true, $attributes);
            $request->attributes->set('_api_normalization_context', $normalizationContext);
        }

        $parameters = $request->attributes->all();
        $resourceClass = $operation->getClass() ?? $attributes['resource_class'];
        try {
            $uriVariables = $this->getOperationUriVariables($operation, $parameters, $resourceClass);
            $data = $this->provider->provide($operation, $uriVariables, $context);
        } catch (InvalidIdentifierException|InvalidUriVariableException $e) {
            throw new NotFoundHttpException('Invalid identifier value or configuration.', $e);
        } catch (ProviderNotFoundException $e) {
            $data = null;
        }

        if (
            null === $data
            && 'POST' !== $operation->getMethod()
            && (
                'PUT' !== $operation->getMethod()
                || ($operation instanceof Put && !($operation->getAllowCreate() ?? false))
            )
        ) {
            throw new NotFoundHttpException('Not Found');
        }

        $request->attributes->set('data', $data);
        $request->attributes->set('previous_data', $this->clone($data));
    }
}
