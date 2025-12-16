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
        if (
            $data instanceof Response
            || !$this->objectMapper
            || !$operation->canWrite()
            || null === $data
            || !$operation->canMap()
        ) {
            return $this->decorated->process($data, $operation, $uriVariables, $context);
        }

        $request = $context['request'] ?? null;
        $resourceClass = $operation->getClass();
        $inputClass = $operation->getInput()['class'] ?? null;
        $outputClass = $operation->getOutput()['class'] ?? null;

        // Get entity class from state options if available
        $stateOptions = $operation->getStateOptions();
        $entityClass = null;
        if ($stateOptions) {
            if (method_exists($stateOptions, 'getEntityClass')) {
                $entityClass = $stateOptions->getEntityClass();
            } elseif (method_exists($stateOptions, 'getDocumentClass')) {
                $entityClass = $stateOptions->getDocumentClass();
            }
        }

        $hasCustomInput = null !== $inputClass && $inputClass !== $resourceClass;
        $hasCustomOutput = null !== $outputClass && $outputClass !== $resourceClass;
        $hasEntityMapping = null !== $entityClass && $entityClass !== $resourceClass;

        // Skip mapping if no custom input/output and no entity mapping needed
        if (!$hasCustomInput && !$hasCustomOutput && !$hasEntityMapping) {
            return $this->decorated->process($data, $operation, $uriVariables, $context);
        }

        // Map input to entity if we have custom input or entity mapping
        if ($hasCustomInput || $hasEntityMapping) {
            $expectedInputClass = $hasCustomInput ? $inputClass : $resourceClass;
            if (!is_a($data, $expectedInputClass, true)) {
                return $this->decorated->process($data, $operation, $uriVariables, $context);
            }

            $data = $this->objectMapper->map($data, $request?->attributes->get('mapped_data'));
        }

        $persisted = $this->decorated->process($data, $operation, $uriVariables, $context);
        $request?->attributes->set('persisted_data', $persisted);

        // Map output back to resource or custom output class
        if ($hasCustomOutput) {
            return $this->objectMapper->map($persisted, $outputClass);
        }

        // If we have entity mapping but no custom output, map back to resource class
        if ($hasEntityMapping) {
            return $this->objectMapper->map($persisted, $resourceClass);
        }

        return $persisted;
    }
}
