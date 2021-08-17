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

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\OperationDataProviderTrait;
use ApiPlatform\Core\Identifier\CompositeIdentifierParser;
use ApiPlatform\Core\Identifier\ContextAwareIdentifierConverterInterface;
use ApiPlatform\Core\Identifier\IdentifierConverterInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ToggleableOperationAttributeTrait;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Core\Util\CloneTrait;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use ApiPlatform\Core\Util\RequestParser;
use ApiPlatform\Exception\InvalidIdentifierException;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
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
    use OperationDataProviderTrait;
    use OperationRequestInitiatorTrait;
    use ToggleableOperationAttributeTrait;

    public const OPERATION_ATTRIBUTE_KEY = 'read';

    private $serializerContextBuilder;
    /** @var ?ProviderInterface */
    private $provider = null;

    public function __construct($collectionDataProvider, $itemDataProvider = null, $subresourceDataProvider = null, SerializerContextBuilderInterface $serializerContextBuilder = null, IdentifierConverterInterface $identifierConverter = null, $resourceMetadataFactory = null)
    {
        $this->collectionDataProvider = $collectionDataProvider;
        $this->itemDataProvider = $itemDataProvider;
        $this->subresourceDataProvider = $subresourceDataProvider;
        $this->serializerContextBuilder = $serializerContextBuilder;
        $this->identifierConverter = $identifierConverter;
        $this->resourceMetadataFactory = $resourceMetadataFactory;

        if ($collectionDataProvider instanceof CollectionDataProviderInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Using a "%s" as first argument of the "%s" is deprecated, use a "%s" instead.', CollectionDataProviderInterface::class, __CLASS__, ProviderInterface::class));
        }

        if ($identifierConverter && !$identifierConverter instanceof ContextAwareIdentifierConverterInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use "%s" instead of "%s".', ContextAwareIdentifierConverterInterface::class, IdentifierConverterInterface::class));
        }

        if (!$resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use "%s" instead of "%s".', ResourceMetadataCollectionFactoryInterface::class, ResourceMetadataFactoryInterface::class));
        }

        if ($resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            $this->resourceMetadataCollectionFactory = $resourceMetadataFactory;
        }

        if ($collectionDataProvider instanceof ProviderInterface) {
            $this->provider = $collectionDataProvider;
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
        $operation = $this->initializeOperation($request);

        if (
            !($attributes = RequestAttributesExtractor::extractAttributes($request))
        ) {
            return;
        }

        if ($this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface &&
            (!$operation || !$operation->canRead() || !$attributes['receive'] || ($operation->isCollection() && !$request->isMethodSafe()))
        ) {
            return;
        }

        // TODO: 3.0 remove condition
        if (
            ($this->resourceMetadataFactory instanceof ResourceMetadataFactoryInterface || !$this->resourceMetadataFactory)
            && (
                !$attributes['receive']
                || $this->isOperationAttributeDisabled($attributes, self::OPERATION_ATTRIBUTE_KEY)
                || $request->isMethod('POST')
            )
        ) {
            return;
        }

        if (null === $filters = $request->attributes->get('_api_filters')) {
            $queryString = RequestParser::getQueryString($request);
            $filters = $queryString ? RequestParser::parseRequestParams($queryString) : null;
        }

        $context = $operation ? ['operation' => $operation, 'legacy_attributes' => $attributes + ['identifiers' => $operation->getIdentifiers(), 'has_composite_identifier' => $operation->getCompositeIdentifier()]] : [];

        if ($filters) {
            $context['filters'] = $filters;
        }

        if ($this->identifierConverter) {
            $context[IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER] = true;
        }

        if ($this->serializerContextBuilder) {
            // Builtin data providers are able to use the serialization context to automatically add join clauses
            $context += $normalizationContext = $this->serializerContextBuilder->createFromRequest($request, true, $attributes);
            $request->attributes->set('_api_normalization_context', $normalizationContext);
        }

        // TODO: 3.0 this is the default
        if ($this->provider && $operation) {
            $shouldParseCompositeIdentifiers = $operation->getCompositeIdentifier() && \count($operation->getIdentifiers()) > 1;
            $parameters = $request->attributes->all();
            $identifiers = [];

            try {
                foreach ($operation->getIdentifiers() as $parameterName => $identifiedBy) {
                    if (!isset($parameters[$parameterName])) {
                        throw new InvalidIdentifierException(sprintf('Parameter "%s" not found, check the identifiers configuration.', $parameterName));
                    }

                    if ($shouldParseCompositeIdentifiers) {
                        $identifiers = CompositeIdentifierParser::parse($parameters[$parameterName]);
                        if (($identifiersNumber = \count($operation->getIdentifiers())) !== ($currentIdentifiersNumber = \count($identifiers))) {
                            throw new InvalidIdentifierException(sprintf('Expected %d identifiers, got %d', $identifiersNumber, $currentIdentifiersNumber));
                        }
                        break;
                    }

                    $identifiers[$parameterName] = $parameters[$parameterName];
                }

                if ($this->identifierConverter) {
                    $identifiers = $this->identifierConverter instanceof ContextAwareIdentifierConverterInterface ? $this->identifierConverter->convert($identifiers, $attributes['resource_class'], ['identifiers' => $operation->getIdentifiers()]) : $this->identifierConverter->convert($identifiers, $attributes['resource_class']);
                }

                $data = $this->provider->provide($attributes['resource_class'], $identifiers, $operation->getName(), $context);
            } catch (InvalidIdentifierException $e) {
                throw new NotFoundHttpException('Invalid identifier value or configuration.', $e);
            }

            if (null === $data) {
                throw new NotFoundHttpException('Not Found');
            }

            $request->attributes->set('data', $data);
            $request->attributes->set('previous_data', $this->clone($data));

            return;
        }

        if ($operation && isset($attributes['operation_name'])) {
            trigger_deprecation('api-platform/core', '2.7', 'Using a #[Resource] without a state provider is deprecated since 2.7 and will not be possible anymore in 3.0.');
            $attributes[sprintf('%s_operation_name', ($operation->getIdentifiers() ?? []) ? 'item' : 'collection')] = $operation->getName();
        }

        if (isset($attributes['collection_operation_name'])) {
            $request->attributes->set('data', $this->getCollectionData($attributes, $context));

            return;
        }

        $data = [];

        try {
            $identifiers = $this->extractIdentifiers($request->attributes->all(), $attributes);
            if (isset($attributes['item_operation_name'])) {
                $data = $this->getItemData($identifiers, $attributes, $context);
            } elseif (isset($attributes['subresource_operation_name'])) {
                // Legacy
                if (null === $this->subresourceDataProvider) {
                    throw new RuntimeException('No subresource data provider.');
                }

                $data = $this->getSubresourceData($identifiers, $attributes, $context);
            }
        } catch (InvalidIdentifierException $e) {
            throw new NotFoundHttpException('Invalid identifier value or configuration.', $e);
        }

        if (null === $data) {
            throw new NotFoundHttpException('Not Found');
        }

        $request->attributes->set('data', $data);
        $request->attributes->set('previous_data', $this->clone($data));
    }
}
