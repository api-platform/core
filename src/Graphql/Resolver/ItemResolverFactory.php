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

namespace ApiPlatform\Core\Graphql\Resolver;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\Graphql\Serializer\ItemNormalizer;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Creates a function retrieving an item to resolve a GraphQL query.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class ItemResolverFactory implements ResolverFactoryInterface
{
    private $iriConverter;
    private $normalizer;
    private $resourceMetadataFactory;

    public function __construct(IriConverterInterface $iriConverter, NormalizerInterface $normalizer, ResourceMetadataFactoryInterface $resourceMetadataFactory)
    {
        $this->iriConverter = $iriConverter;
        $this->normalizer = $normalizer;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    public function __invoke(string $resourceClass = null, string $rootClass = null, string $operationName = null): callable
    {
        return function ($source, $args, $context, ResolveInfo $info) use ($resourceClass) {
            // Data already fetched and normalized (field or nested resource)
            if (isset($source[$info->fieldName])) {
                return $source[$info->fieldName];
            }

            if (!isset($args['id'])) {
                return null;
            }

            // TODO: initialize the EagerLoading extension
            try {
                $item = $this->iriConverter->getItemFromIri($args['id']);
            } catch (ItemNotFoundException $e) {
                return null;
            }

            $normalizationContext = $this->resourceMetadataFactory->create($resourceClass)->getGraphqlAttribute('query', 'normalization_context', [], true);

            return $this->normalizer->normalize($item, ItemNormalizer::FORMAT, $normalizationContext + ['attributes' => $info->getFieldSelection(PHP_INT_MAX)]);
        };
    }
}
