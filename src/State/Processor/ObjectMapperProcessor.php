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
use ApiPlatform\State\ObjectMapper\ClearObjectMapInterface;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

/**
 * @implements ProcessorInterface<mixed,mixed>
 */
final class ObjectMapperProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<mixed,mixed> $decorated
     */
    public function __construct(
        private readonly ?ObjectMapperInterface $objectMapper,
        private readonly ProcessorInterface $decorated,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (!$this->objectMapper || !$operation->canWrite()) {
            return $this->decorated->process($data, $operation, $uriVariables, $context);
        }

        if (!(new \ReflectionClass($operation->getClass()))->getAttributes(Map::class)) {
            return $this->decorated->process($data, $operation, $uriVariables, $context);
        }

        $data = $this->objectMapper->map($this->decorated->process($this->objectMapper->map($data), $operation, $uriVariables, $context), $operation->getClass());

        if ($this->objectMapper instanceof ClearObjectMapInterface) {
            $this->objectMapper->clearObjectMap();
        }

        return $data;
    }
}
