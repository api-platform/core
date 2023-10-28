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
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\State\ProcessorInterface;

/**
 * Write stage of GraphQL resolvers.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class WriteStage implements WriteStageInterface
{
    public function __construct(private readonly ProcessorInterface $processor, private readonly SerializerContextBuilderInterface $serializerContextBuilder)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(?object $data, string $resourceClass, Operation $operation, array $context): ?object
    {
        if (null === $data || !($operation->canWrite() ?? true)) {
            return $data;
        }

        $denormalizationContext = $this->serializerContextBuilder->create($resourceClass, $operation, $context, false);

        return $this->processor->process($data, $operation, [], ['operation' => $operation] + $denormalizationContext);
    }
}
