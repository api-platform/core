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
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\ObjectMapper\Metadata\ObjectMapperMetadataFactoryInterface;
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
        private readonly ?ObjectMapperMetadataFactoryInterface $objectMapperMetadata = null,
    ) {
        // TODO: 4.3 add this deprecation
        // if (!$objectMapperMetadata) {
        //     trigger_deprecation('api-platform/state', '4.3', 'Not injecting "%s" in "%s" will not be possible anymore in 5.0.', ObjectMapperMetadataFactoryInterface::class, __CLASS__);
        // }
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $class = $operation->getInput()['class'] ?? $operation->getClass();

        if (
            !$this->objectMapper
            || !$operation->canWrite()
            || null === $data
            || !is_a($data, $class, true)
        ) {
            return $this->decorated->process($data, $operation, $uriVariables, $context);
        }

        if ($this->objectMapperMetadata) {
            if (!$this->objectMapperMetadata->create($data)) {
                return $this->decorated->process($data, $operation, $uriVariables, $context);
            }
        } elseif (!(new \ReflectionClass($class))->getAttributes(Map::class)) {
            return $this->decorated->process($data, $operation, $uriVariables, $context);
        }

        // return the Resource representation of the persisted entity
        return $this->objectMapper->map(
            // persist the entity
            $this->decorated->process(
                // maps the Resource to an Entity
                $this->objectMapper->map($data, $context['request']?->attributes->get('entity_data')),
                $operation,
                $uriVariables,
                $context,
            ),
            $operation->getClass()
        );
    }
}
