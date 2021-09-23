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

use ApiPlatform\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Deserialize stage of GraphQL resolvers.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class DeserializeStage implements DeserializeStageInterface
{
    private $resourceMetadataCollectionFactory;
    private $denormalizer;
    private $serializerContextBuilder;

    public function __construct(ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, DenormalizerInterface $denormalizer, SerializerContextBuilderInterface $serializerContextBuilder)
    {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->denormalizer = $denormalizer;
        $this->serializerContextBuilder = $serializerContextBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke($objectToPopulate, string $resourceClass, string $operationName, array $context)
    {
        $resourceMetadataCollection = $this->resourceMetadataCollectionFactory->create($resourceClass);
        $operation = $resourceMetadataCollection->getGraphQlOperation($operationName);
        if (!($operation->canDeserialize() ?? true)) {
            return $objectToPopulate;
        }

        $denormalizationContext = $this->serializerContextBuilder->create($resourceClass, $operationName, $context, false);
        if (null !== $objectToPopulate) {
            $denormalizationContext[AbstractNormalizer::OBJECT_TO_POPULATE] = $objectToPopulate;
        }

        $item = $this->denormalizer->denormalize($context['args']['input'], $resourceClass, ItemNormalizer::FORMAT, $denormalizationContext);

        if (!\is_object($item)) {
            throw new \UnexpectedValueException('Expected item to be an object.');
        }

        return $item;
    }
}
