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
 * Maps the persisted entity back to the API resource (DTO) after persistence.
 *
 * @implements ProcessorInterface<mixed,mixed>
 */
final class ObjectMapperOutputProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<mixed,mixed>|null $decorated
     */
    public function __construct(
        private readonly ?ObjectMapperInterface $objectMapper,
        private readonly ?ProcessorInterface $decorated = null,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (
            $data instanceof Response
            || !$this->objectMapper
            || !($operation->canWrite() ?? true)
            || null === $data
            || !$operation->canMap()
        ) {
            return $this->decorated ? $this->decorated->process($data, $operation, $uriVariables, $context) : $data;
        }

        $request = $context['request'] ?? null;
        $request?->attributes->set('persisted_data', $data);
        $dto = $this->objectMapper->map($data, $operation->getClass());

        return $this->decorated ? $this->decorated->process($dto, $operation, $uriVariables, $context) : $dto;
    }
}
