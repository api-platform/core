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

use ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\ProcessorInterface;

/**
 * Write stage of GraphQL resolvers.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class WriteStage implements WriteStageInterface
{
    private $resourceMetadataCollectionFactory;
    private $processor;
    private $serializerContextBuilder;

    public function __construct(ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, ProcessorInterface $processor, SerializerContextBuilderInterface $serializerContextBuilder)
    {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->processor = $processor;
        $this->serializerContextBuilder = $serializerContextBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke($data, string $resourceClass, string $operationName, array $context)
    {
        $resourceMetadataCollection = $this->resourceMetadataCollectionFactory->create($resourceClass);
        $operation = $resourceMetadataCollection->getOperation($operationName);
        if (null === $data || !($operation->canWrite() ?? true)) {
            return $data;
        }

        $denormalizationContext = $this->serializerContextBuilder->create($resourceClass, $operationName, $context, false);

        return $this->processor->process($data, [], $operation->getName(), ['operation' => $operation] + $denormalizationContext);
    }
}
