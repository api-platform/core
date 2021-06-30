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

use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Translation\ResourceTranslatorInterface;
use ApiPlatform\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class SerializerContextBuilder implements SerializerContextBuilderInterface
{
    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory,
        private readonly ?ResourceTranslatorInterface $resourceTranslator = null,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function createFromRequest(Request $request, bool $normalization, array $attributes = null): array
    {
        if (null === $attributes && !$attributes = RequestAttributesExtractor::extractAttributes($request)) {
            throw new RuntimeException('Request attributes are not valid.');
        }

        $operation = $attributes['operation'] ?? $this->resourceMetadataFactory->create($attributes['resource_class'])->getOperation($attributes['operation_name']);
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

        if ($operation->getTypes()) {
            $context['types'] = $operation->getTypes();
        }

        if ($operation->getUriVariables()) {
            $context['uri_variables'] = [];

            foreach (array_keys($operation->getUriVariables()) as $parameterName) {
                $context['uri_variables'][$parameterName] = $request->attributes->get($parameterName);
            }
        }

        if ($this->resourceTranslator) {
            $context['all_translations_enabled'] = $this->resourceTranslator->isAllTranslationsEnabled($attributes['resource_class'], $request->query->all());
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

        return $context;
    }
}
