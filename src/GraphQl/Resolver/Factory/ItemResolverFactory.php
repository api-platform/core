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

namespace ApiPlatform\Core\GraphQl\Resolver\Factory;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\GraphQl\Resolver\FieldsToAttributesTrait;
use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use ApiPlatform\Core\GraphQl\Resolver\ResourceAccessCheckerTrait;
use ApiPlatform\Core\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Security\ResourceAccessCheckerInterface;
use ApiPlatform\Core\Util\ClassInfoTrait;
use GraphQL\Type\Definition\ResolveInfo;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Creates a function retrieving an item to resolve a GraphQL query.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemResolverFactory implements ResolverFactoryInterface
{
    use ClassInfoTrait;
    use FieldsToAttributesTrait;
    use ResourceAccessCheckerTrait;

    private $iriConverter;
    private $queryResolverLocator;
    private $resourceAccessChecker;
    private $normalizer;
    private $resourceMetadataFactory;

    public function __construct(IriConverterInterface $iriConverter, ContainerInterface $queryResolverLocator, NormalizerInterface $normalizer, ResourceMetadataFactoryInterface $resourceMetadataFactory, ResourceAccessCheckerInterface $resourceAccessChecker = null)
    {
        $this->iriConverter = $iriConverter;
        $this->queryResolverLocator = $queryResolverLocator;
        $this->normalizer = $normalizer;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->resourceAccessChecker = $resourceAccessChecker;
    }

    public function __invoke(?string $resourceClass = null, ?string $rootClass = null, ?string $operationName = null): callable
    {
        return function ($source, $args, $context, ResolveInfo $info) use ($resourceClass, $operationName) {
            // Data already fetched and normalized (field or nested resource)
            if (isset($source[$info->fieldName])) {
                return $source[$info->fieldName];
            }

            $baseNormalizationContext = ['attributes' => $this->fieldsToAttributes($info)];
            $item = $this->getItem($args, $baseNormalizationContext);
            $resourceClass = $this->getResourceClass($item, $resourceClass);

            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

            $queryResolverId = $resourceMetadata->getGraphqlAttribute($operationName ?? 'query', 'item_query');
            if (null !== $queryResolverId) {
                /** @var QueryItemResolverInterface $queryResolver */
                $queryResolver = $this->queryResolverLocator->get($queryResolverId);
                $item = $queryResolver($item, ['source' => $source, 'args' => $args, 'info' => $info]);
                $resourceClass = $this->getResourceClass($item, $resourceClass, sprintf('Custom query resolver "%s"', $queryResolverId).' has to return an item of class %s but returned an item of class %s');
            }

            $this->canAccess($this->resourceAccessChecker, $resourceMetadata, $resourceClass, $info, $item, $operationName ?? 'query');

            $normalizationContext = $resourceMetadata->getGraphqlAttribute($operationName ?? 'query', 'normalization_context', [], true);

            return $this->normalizer->normalize($item, ItemNormalizer::FORMAT, $normalizationContext + $baseNormalizationContext);
        };
    }

    /**
     * @return object|null
     */
    private function getItem($args, array $baseNormalizationContext)
    {
        if (!isset($args['id'])) {
            return null;
        }

        try {
            $item = $this->iriConverter->getItemFromIri($args['id'], $baseNormalizationContext);
        } catch (ItemNotFoundException $e) {
            return null;
        }

        return $item;
    }

    /**
     * @param object|null $item
     *
     * @throws RuntimeException
     */
    private function getResourceClass($item, ?string $resourceClass, string $errorMessage = 'Resolver only handles items of class %s but retrieved item is of class %s'): ?string
    {
        if (null === $item) {
            return $resourceClass;
        }

        $itemClass = $this->getObjectClass($item);

        if (null === $resourceClass) {
            return $itemClass;
        }

        if ($resourceClass !== $itemClass) {
            throw new RuntimeException(sprintf($errorMessage, $resourceClass, $itemClass));
        }

        return $resourceClass;
    }
}
