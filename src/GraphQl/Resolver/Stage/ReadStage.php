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

namespace ApiPlatform\GraphQl\Resolver\Stage;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Exception\ItemNotFoundException;
use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\GraphQl\Resolver\Util\IdentifierTrait;
use ApiPlatform\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Util\ArrayTrait;
use ApiPlatform\Util\ClassInfoTrait;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Read stage of GraphQL resolvers.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class ReadStage implements ReadStageInterface
{
    use ArrayTrait;
    use ClassInfoTrait;
    use IdentifierTrait;

    private $resourceMetadataCollectionFactory;
    private $iriConverter;
    private $collectionDataProvider;
    private $subresourceDataProvider;
    private $serializerContextBuilder;
    private $nestingSeparator;

    public function __construct(ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, IriConverterInterface $iriConverter, ContextAwareCollectionDataProviderInterface $collectionDataProvider, SubresourceDataProviderInterface $subresourceDataProvider, SerializerContextBuilderInterface $serializerContextBuilder, string $nestingSeparator)
    {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->iriConverter = $iriConverter;
        $this->collectionDataProvider = $collectionDataProvider;
        $this->subresourceDataProvider = $subresourceDataProvider;
        $this->serializerContextBuilder = $serializerContextBuilder;
        $this->nestingSeparator = $nestingSeparator;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(?string $resourceClass, ?string $rootClass, string $operationName, array $context)
    {
        $operation = null;
        try {
            $operation = $resourceClass ? $this->resourceMetadataCollectionFactory->create($resourceClass)->getOperation($operationName) : null;
        } catch (OperationNotFoundException $e) {
            // ReadStage may be invoked without an existing operation
        }

        if ($operation && !($operation->canRead() ?? true)) {
            return $context['is_collection'] ? [] : null;
        }

        $args = $context['args'];
        $normalizationContext = $this->serializerContextBuilder->create($resourceClass, $operationName, $context, true);

        if (!$context['is_collection']) {
            $identifier = $this->getIdentifierFromContext($context);
            $item = $this->getItem($identifier, $normalizationContext);

            if ($identifier && ($context['is_mutation'] || $context['is_subscription'])) {
                if (null === $item) {
                    throw new NotFoundHttpException(sprintf('Item "%s" not found.', $args['input']['id']));
                }

                if ($resourceClass !== $this->getObjectClass($item)) {
                    throw new \UnexpectedValueException(sprintf('Item "%s" did not match expected type "%s".', $args['input']['id'], $operation->getShortName()));
                }
            }

            return $item;
        }

        if (null === $rootClass) {
            return [];
        }

        $normalizationContext['filters'] = $this->getNormalizedFilters($args);

        $source = $context['source'];
        /** @var ResolveInfo $info */
        $info = $context['info'];
        if (isset($source[$rootProperty = $info->fieldName], $source[ItemNormalizer::ITEM_IDENTIFIERS_KEY], $source[ItemNormalizer::ITEM_RESOURCE_CLASS_KEY])) {
            $rootResolvedFields = $source[ItemNormalizer::ITEM_IDENTIFIERS_KEY];
            $rootResolvedClass = $source[ItemNormalizer::ITEM_RESOURCE_CLASS_KEY];
            $subresourceCollection = $this->getSubresource($rootResolvedClass, $rootResolvedFields, $rootProperty, $resourceClass, $normalizationContext, $operationName);
            if (!is_iterable($subresourceCollection)) {
                throw new \UnexpectedValueException('Expected subresource collection to be iterable.');
            }

            return $subresourceCollection;
        }

        return $this->collectionDataProvider->getCollection($resourceClass, $operationName, $normalizationContext);
    }

    /**
     * @return object|null
     */
    private function getItem(?string $identifier, array $normalizationContext)
    {
        if (null === $identifier) {
            return null;
        }

        try {
            $item = $this->iriConverter->getItemFromIri($identifier, $normalizationContext);
        } catch (ItemNotFoundException $e) {
            return null;
        }

        return $item;
    }

    private function getNormalizedFilters(array $args): array
    {
        $filters = $args;

        foreach ($filters as $name => $value) {
            if (\is_array($value)) {
                if (strpos($name, '_list')) {
                    $name = substr($name, 0, \strlen($name) - \strlen('_list'));
                }

                // If the value contains arrays, we need to merge them for the filters to understand this syntax, proper to GraphQL to preserve the order of the arguments.
                if ($this->isSequentialArrayOfArrays($value)) {
                    if (\count($value[0]) > 1) {
                        $deprecationMessage = "The filter syntax \"$name: {";
                        $filterArgsOld = [];
                        $filterArgsNew = [];
                        foreach ($value[0] as $filterArgName => $filterArgValue) {
                            $filterArgsOld[] = "$filterArgName: \"$filterArgValue\"";
                            $filterArgsNew[] = sprintf('{%s: "%s"}', $filterArgName, $filterArgValue);
                        }
                        $deprecationMessage .= sprintf('%s}" is deprecated since API Platform 2.6, use the following syntax instead: "%s: [%s]".', implode(', ', $filterArgsOld), $name, implode(', ', $filterArgsNew));
                        @trigger_error($deprecationMessage, \E_USER_DEPRECATED);
                    }
                    $value = array_merge(...$value);
                }
                $filters[$name] = $this->getNormalizedFilters($value);
            }

            if (\is_string($name) && strpos($name, $this->nestingSeparator)) {
                // Gives a chance to relations/nested fields.
                $index = array_search($name, array_keys($filters), true);
                $filters =
                    \array_slice($filters, 0, $index + 1) +
                    [str_replace($this->nestingSeparator, '.', $name) => $value] +
                    \array_slice($filters, $index + 1);
            }
        }

        return $filters;
    }

    /**
     * @return iterable|object|null
     */
    private function getSubresource(string $rootResolvedClass, array $rootResolvedFields, string $rootProperty, string $subresourceClass, array $normalizationContext, string $operationName)
    {
        $resolvedIdentifiers = [];
        $rootIdentifiers = array_keys($rootResolvedFields);
        foreach ($rootIdentifiers as $rootIdentifier) {
            $resolvedIdentifiers[$rootIdentifier] = [$rootResolvedClass, $rootIdentifier];
        }

        return $this->subresourceDataProvider->getSubresource($subresourceClass, $rootResolvedFields, $normalizationContext + [
            'property' => $rootProperty,
            'identifiers' => $resolvedIdentifiers,
            'collection' => true,
        ], $operationName);
    }
}
