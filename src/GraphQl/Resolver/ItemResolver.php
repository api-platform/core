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

namespace ApiPlatform\Core\GraphQl\Resolver;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Security\ResourceAccessCheckerInterface;
use ApiPlatform\Core\Util\ClassInfoTrait;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Creates a function retrieving an item to resolve a GraphQL query.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemResolver
{
    use ClassInfoTrait;
    use FieldsToAttributesTrait;
    use ResourceAccessCheckerTrait;

    private $iriConverter;
    private $resourceAccessChecker;
    private $normalizer;
    private $resourceMetadataFactory;

    public function __construct(IriConverterInterface $iriConverter, NormalizerInterface $normalizer, ResourceMetadataFactoryInterface $resourceMetadataFactory, ResourceAccessCheckerInterface $resourceAccessChecker = null)
    {
        $this->iriConverter = $iriConverter;
        $this->normalizer = $normalizer;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->resourceAccessChecker = $resourceAccessChecker;
    }

    public function __invoke($source, $args, $context, ResolveInfo $info)
    {
        // Data already fetched and normalized (field or nested resource)
        if (isset($source[$info->fieldName])) {
            return $source[$info->fieldName];
        }

        if (!isset($args['id'])) {
            return null;
        }

        $baseNormalizationContext = ['attributes' => $this->fieldsToAttributes($info)];
        try {
            $item = $this->iriConverter->getItemFromIri($args['id'], $baseNormalizationContext);
        } catch (ItemNotFoundException $e) {
            return null;
        }

        $resourceClass = $this->getObjectClass($item);
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $this->canAccess($this->resourceAccessChecker, $resourceMetadata, $resourceClass, $info, [
            'object' => $item,
            'previous_object' => \is_object($item) ? clone $item : $item,
        ], 'query');

        $normalizationContext = $resourceMetadata->getGraphqlAttribute('query', 'normalization_context', [], true);
        $normalizationContext['resource_class'] = $resourceClass;

        return $this->normalizer->normalize($item, ItemNormalizer::FORMAT, $normalizationContext + $baseNormalizationContext);
    }
}
