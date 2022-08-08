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
use ApiPlatform\Metadata\GraphQl\Operation;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Deserialize stage of GraphQL resolvers.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class DeserializeStage implements DeserializeStageInterface
{
    public function __construct(private readonly DenormalizerInterface $denormalizer, private readonly SerializerContextBuilderInterface $serializerContextBuilder)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(?object $objectToPopulate, string $resourceClass, Operation $operation, array $context): ?object
    {
        if (!($operation->canDeserialize() ?? true)) {
            return $objectToPopulate;
        }

        $denormalizationContext = $this->serializerContextBuilder->create($resourceClass, $operation, $context, false);
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
