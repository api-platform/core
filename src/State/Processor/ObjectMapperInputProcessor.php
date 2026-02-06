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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

/**
 * Maps the API resource (DTO) to the entity before persistence.
 *
 * @implements ProcessorInterface<mixed,mixed>
 */
final class ObjectMapperInputProcessor implements ProcessorInterface
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
        $class = $operation->getInput()['class'] ?? $operation->getClass();

        if (
            $data instanceof Response
            || !$this->objectMapper
            || !($operation->canWrite() ?? true)
            || null === $data
            || null === $class
            || !is_a($data, $class, true)
            || !$operation->canMap()
        ) {
            return $this->decorated->process($data, $operation, $uriVariables, $context);
        }

        $request = $context['request'] ?? null;
        $mapped = $this->objectMapper->map($data, $request?->attributes->get('mapped_data'));

        return $this->decorated->process($mapped, $operation, $uriVariables, $context);
    }
}
