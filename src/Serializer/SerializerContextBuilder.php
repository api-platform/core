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

namespace ApiPlatform\Serializer;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Error as ErrorOperation;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Util\AttributesExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class SerializerContextBuilder implements SerializerContextBuilderInterface
{
    public function __construct(private readonly ?ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory = null, private readonly bool $debug = false)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function createFromRequest(Request $request, bool $normalization, ?array $attributes = null): array
    {
        if (null === $attributes && !$attributes = AttributesExtractor::extractAttributes($request->attributes->all())) {
            throw new RuntimeException('Request attributes are not valid.');
        }

        if (!($operation = $attributes['operation'] ?? null)) {
            if (!$this->resourceMetadataFactory) {
                throw new RuntimeException('No operation');
            }

            $operation = $this->resourceMetadataFactory->create($attributes['resource_class'])->getOperation($attributes['operation_name'] ?? null);
        }

        $context = $normalization ? ($operation->getNormalizationContext() ?? []) : ($operation->getDenormalizationContext() ?? []);
        $context['operation_name'] = $operation->getName();
        $context['operation'] = $operation;
        $context['resource_class'] = $attributes['resource_class'];
        $context['skip_null_values'] ??= true;
        $context['iri_only'] ??= false;
        $context['request_uri'] = $request->getRequestUri();
        $context['uri'] = $request->getUri();
        $context['input'] = $operation->getInput();
        $context['output'] = $operation->getOutput();

        // Special case as this is usually handled by our OperationContextTrait, here we want to force the IRI in the response
        if (!$operation instanceof CollectionOperationInterface && method_exists($operation, 'getItemUriTemplate') && $operation->getItemUriTemplate()) {
            $context['item_uri_template'] = $operation->getItemUriTemplate();
        }

        if ($types = $operation->getTypes()) {
            $context['types'] = $types;
        }

        // TODO: remove this as uri variables are available in the SerializerProcessor but correctly parsed
        if ($operation->getUriVariables()) {
            $context['uri_variables'] = [];

            foreach (array_keys($operation->getUriVariables()) as $parameterName) {
                $context['uri_variables'][$parameterName] = $request->attributes->get($parameterName);
            }
        }

        if (null === $context['output'] && ($options = $operation?->getStateOptions()) && $options instanceof Options && $options->getEntityClass()) {
            $context['force_resource_class'] = $operation->getClass();
        }

        if ($this->debug && isset($context['groups']) && $operation instanceof ErrorOperation) {
            if (!\is_array($context['groups'])) {
                $context['groups'] = (array) $context['groups'];
            }

            $context['groups'][] = 'trace';
        }

        if (!$normalization) {
            if (!isset($context['api_allow_update'])) {
                $context['api_allow_update'] = \in_array($method = $request->getMethod(), ['PUT', 'PATCH'], true);

                if ($context['api_allow_update'] && 'PATCH' === $method) {
                    $context['deep_object_to_populate'] ??= true;
                }
            }

            if ('csv' === (method_exists(Request::class, 'getContentTypeFormat') ? $request->getContentTypeFormat() : $request->getContentType())) {
                $context[CsvEncoder::AS_COLLECTION_KEY] = false;
            }
        }
        if ($operation->getCollectDenormalizationErrors() ?? false) {
            $context[DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS] = true;
        }

        // to keep the cache computation smaller, we have "operation_name" and "iri" anyways
        $context[AbstractObjectNormalizer::EXCLUDE_FROM_CACHE_KEY][] = 'root_operation';
        $context[AbstractObjectNormalizer::EXCLUDE_FROM_CACHE_KEY][] = 'operation';

        // JSON API see JsonApiProvider
        if ($included = $request->attributes->get('_api_included')) {
            $context['api_included'] = $included;
        }

        return $context;
    }
}
