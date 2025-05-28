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

namespace ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class ObjectMapperProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ObjectMapperInterface $objectMapper,
        private readonly ProcessorInterface $decorated,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (!(new \ReflectionClass($operation->getClass()))->getAttributes(Map::class)) {
            return $this->decorated->process($data, $operation, $uriVariables, $context);
        }

        return $this->objectMapper->map($this->decorated->process($this->objectMapper->map($data, $context['data'] ?? null), $operation, $uriVariables, $context));
    }
}
