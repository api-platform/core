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

namespace ApiPlatform\JsonSchema;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Util\ResourceClassInfoTrait;

/**
 * @internal
 */
trait ResourceMetadataTrait
{
    use ResourceClassInfoTrait;

    private function findOutputClass(string $className, string $type, Operation $operation, ?array $serializerContext): ?string
    {
        $inputOrOutput = ['class' => $className];
        $inputOrOutput = Schema::TYPE_OUTPUT === $type ? ($operation->getOutput() ?? $inputOrOutput) : ($operation->getInput() ?? $inputOrOutput);
        $forceSubschema = $serializerContext[SchemaFactory::FORCE_SUBSCHEMA] ?? false;

        return $forceSubschema ? ($inputOrOutput['class'] ?? $inputOrOutput->class ?? $operation->getClass()) : ($inputOrOutput['class'] ?? $inputOrOutput->class ?? null);
    }

    private function findOperation(string $className, string $type, ?Operation $operation, ?array $serializerContext, ?string $format = null): Operation
    {
        if (null === $operation) {
            if (null === $this->resourceMetadataFactory) {
                return new HttpOperation();
            }
            $resourceMetadataCollection = $this->resourceMetadataFactory->create($className);

            try {
                $operation = $resourceMetadataCollection->getOperation();
            } catch (OperationNotFoundException $e) {
                $operation = new HttpOperation();
            }
            $forceSubschema = $serializerContext[SchemaFactory::FORCE_SUBSCHEMA] ?? false;
            if ($operation->getShortName() === $this->getShortClassName($className) && $forceSubschema) {
                $operation = new HttpOperation();
            }

            return $this->findOperationForType($resourceMetadataCollection, $type, $operation, $forceSubschema ? null : $format);
        }

        // The best here is to use an Operation when calling `buildSchema`, we try to do a smart guess otherwise
        if ($this->resourceMetadataFactory && !$operation->getClass()) {
            $resourceMetadataCollection = $this->resourceMetadataFactory->create($className);

            if ($operation->getName()) {
                return $resourceMetadataCollection->getOperation($operation->getName());
            }

            return $this->findOperationForType($resourceMetadataCollection, $type, $operation, $format);
        }

        return $operation;
    }

    private function findOperationForType(ResourceMetadataCollection $resourceMetadataCollection, string $type, Operation $operation, ?string $format = null): Operation
    {
        $lookForCollection = $operation instanceof CollectionOperationInterface;
        // Find the operation and use the first one that matches criterias
        foreach ($resourceMetadataCollection as $resourceMetadata) {
            foreach ($resourceMetadata->getOperations() ?? [] as $op) {
                if (!$lookForCollection && $op instanceof CollectionOperationInterface) {
                    continue;
                }

                if (Schema::TYPE_INPUT === $type && \in_array($op->getMethod(), ['POST', 'PATCH', 'PUT'], true)) {
                    $operation = $op;
                    break 2;
                }

                if ($format && Schema::TYPE_OUTPUT === $type && \array_key_exists($format, $op->getOutputFormats() ?? [])) {
                    $operation = $op;
                    break 2;
                }
            }
        }

        return $operation;
    }

    private function getSerializerContext(Operation $operation, string $type = Schema::TYPE_OUTPUT): array
    {
        return Schema::TYPE_OUTPUT === $type ? ($operation->getNormalizationContext() ?? []) : ($operation->getDenormalizationContext() ?? []);
    }

    private function getShortClassName(string $fullyQualifiedName): string
    {
        $parts = explode('\\', $fullyQualifiedName);

        return end($parts);
    }
}
