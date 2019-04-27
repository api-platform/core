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

namespace ApiPlatform\Core\GraphQl\Resolver\Stage;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\Core\GraphQl\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\ClassInfoTrait;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Read stage of GraphQL resolvers.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class ReadStage implements ReadStageInterface
{
    use ClassInfoTrait;

    private $resourceMetadataFactory;
    private $iriConverter;
    private $collectionDataProvider;
    private $subresourceDataProvider;
    private $serializerContextBuilder;
    private $nestingSeparator;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, IriConverterInterface $iriConverter, ContextAwareCollectionDataProviderInterface $collectionDataProvider, SubresourceDataProviderInterface $subresourceDataProvider, SerializerContextBuilderInterface $serializerContextBuilder, string $nestingSeparator)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
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
        $resourceMetadata = $resourceClass ? $this->resourceMetadataFactory->create($resourceClass) : null;
        if ($resourceMetadata && !$resourceMetadata->getGraphqlAttribute($operationName, 'read', true, true)) {
            return $context['is_collection'] ? [] : null;
        }

        $args = $context['args'];
        /** @var ResolveInfo $info */
        $info = $context['info'];

        $normalizationContext = $this->serializerContextBuilder->create($resourceClass, $operationName, $context, true);

        if (!$context['is_collection']) {
            $identifier = $this->getIdentifier($context);
            $item = $this->getItem($identifier, $normalizationContext);

            if ($identifier && $context['is_mutation']) {
                if (null === $item) {
                    throw Error::createLocatedError(sprintf('Item "%s" not found.', $args['input']['id']), $info->fieldNodes, $info->path);
                }

                if ($resourceClass !== $this->getObjectClass($item)) {
                    throw Error::createLocatedError(sprintf('Item "%s" did not match expected type "%s".', $args['input']['id'], $resourceMetadata->getShortName()), $info->fieldNodes, $info->path);
                }
            }

            return $item;
        }

        if (null === $rootClass) {
            return [];
        }

        $normalizationContext['filters'] = $this->getNormalizedFilters($args);

        $source = $context['source'];
        if (isset($source[$rootProperty = $info->fieldName], $source[ItemNormalizer::ITEM_IDENTIFIERS_KEY])) {
            $rootResolvedFields = $source[ItemNormalizer::ITEM_IDENTIFIERS_KEY];
            $subresourceCollection = $this->getSubresource($rootClass, $rootResolvedFields, $rootProperty, $resourceClass, $normalizationContext, $operationName);
            if (!is_iterable($subresourceCollection)) {
                throw new \UnexpectedValueException('Expected subresource collection to be iterable');
            }

            return $subresourceCollection;
        }

        return $this->collectionDataProvider->getCollection($resourceClass, $operationName, $normalizationContext);
    }

    private function getIdentifier(array $context): ?string
    {
        $args = $context['args'];

        if ($context['is_mutation']) {
            return $args['input']['id'] ?? null;
        }

        return $args['id'] ?? null;
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
                $filters[$name] = $this->getNormalizedFilters($value);
            }

            if (\is_string($name) && strpos($name, $this->nestingSeparator)) {
                // Gives a chance to relations/nested fields.
                $filters[str_replace($this->nestingSeparator, '.', $name)] = $value;
            }
        }

        return $filters;
    }

    /**
     * @return iterable|object|null
     */
    private function getSubresource(string $rootClass, array $rootResolvedFields, string $rootProperty, string $subresourceClass, array $normalizationContext, string $operationName)
    {
        $resolvedIdentifiers = [];
        $rootIdentifiers = array_keys($rootResolvedFields);
        foreach ($rootIdentifiers as $rootIdentifier) {
            $resolvedIdentifiers[] = [$rootIdentifier, $rootClass];
        }

        return $this->subresourceDataProvider->getSubresource($subresourceClass, $rootResolvedFields, $normalizationContext + [
            'property' => $rootProperty,
            'identifiers' => $resolvedIdentifiers,
            'collection' => true,
        ], $operationName);
    }
}
