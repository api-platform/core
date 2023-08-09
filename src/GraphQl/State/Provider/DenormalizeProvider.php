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

namespace ApiPlatform\GraphQl\State\Provider;

use ApiPlatform\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * A denormalization provider for GraphQl.
 */
final class DenormalizeProvider implements ProviderInterface
{
    /**
     * @param ProviderInterface<object> $decorated
     */
    public function __construct(private readonly ProviderInterface $decorated, private readonly DenormalizerInterface $denormalizer, private readonly SerializerContextBuilderInterface $serializerContextBuilder)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $data = $this->decorated->provide($operation, $uriVariables, $context);

        if (!($operation->canDeserialize() ?? true) || (!$operation instanceof Mutation)) {
            return $data;
        }

        $denormalizationContext = $this->serializerContextBuilder->create($operation->getClass(), $operation, $context, normalization: false);

        if (null !== $data) {
            $denormalizationContext[AbstractNormalizer::OBJECT_TO_POPULATE] = $data;
        }

        $item = $this->denormalizer->denormalize($context['args']['input'], $operation->getClass(), ItemNormalizer::FORMAT, $denormalizationContext);

        if (!\is_object($item)) {
            throw new \UnexpectedValueException('Expected item to be an object.');
        }

        return $item;
    }
}
