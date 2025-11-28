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
        $class = $operation->getInput()['class'] ?? $operation->getClass();

        if (
            $data instanceof Response
            || !$this->objectMapper
            || !$operation->canWrite()
            || null === $data
            || !is_a($data, $class, true)
            || !$operation->canMap()
        ) {
            return $this->decorated->process($data, $operation, $uriVariables, $context);
        }

        $request = $context['request'] ?? null;
        $persisted = $this->decorated->process(
            // maps the Resource to an Entity
            $this->objectMapper->map($data, $request?->attributes->get('mapped_data')),
            $operation,
            $uriVariables,
            $context,
        );

        $request?->attributes->set('persisted_data', $persisted);

        // return the Resource representation of the persisted entity
        return $this->objectMapper->map(
            // persist the entity
            $persisted,
            $operation->getClass()
        );
    }
}
