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

namespace ApiPlatform\Core\Serializer;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;

trait InputOutputMetadataTrait
{
    /**
     * @var ResourceMetadataFactoryInterface|ResourceMetadataCollectionFactoryInterface|null
     */
    protected $resourceMetadataFactory;

    protected function getInputClass(string $class, array $context = []): ?string
    {
        if (!$this->resourceMetadataFactory || !$this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            return $this->getInputOutputMetadata($class, 'input', $context);
        }

        if (null !== ($context['input']['class'] ?? null)) {
            return $context['input']['class'];
        }

        $operation = $context['operation'] ?? null;
        if (!$operation) {
            try {
                $operation = $this->resourceMetadataFactory->create($class)->getOperation($context['operation_name'] ?? null);
            } catch (OperationNotFoundException $e) {
                return null;
            }
        }

        return $operation ? $operation->getInput()['class'] ?? null : null;
    }

    protected function getOutputClass(string $class, array $context = []): ?string
    {
        if (!$this->resourceMetadataFactory || !$this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            return $this->getInputOutputMetadata($class, 'output', $context);
        }

        if (null !== ($context['output']['class'] ?? null)) {
            return $context['output']['class'];
        }

        $operation = $context['operation'] ?? null;
        if (null === $operation) {
            try {
                $operation = $this->resourceMetadataFactory->create($class)->getOperation($context['operation_name'] ?? null);
            } catch (OperationNotFoundException $e) {
                return null;
            }
        }

        return $operation ? $operation->getOutput()['class'] ?? null : null;
    }

    // TODO: remove in 3.0
    private function getInputOutputMetadata(string $class, string $inputOrOutput, array $context)
    {
        if (null === $this->resourceMetadataFactory || null !== ($context[$inputOrOutput]['class'] ?? null)) {
            return $context[$inputOrOutput]['class'] ?? null;
        }

        try {
            $metadata = $this->resourceMetadataFactory->create($class);
        } catch (ResourceClassNotFoundException $e) {
            return null;
        }

        return $metadata->getAttribute($inputOrOutput)['class'] ?? null;
    }
}
