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

namespace ApiPlatform\Serializer;

use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;

trait InputOutputMetadataTrait
{
    /**
     * @var ResourceMetadataCollectionFactoryInterface|null
     */
    protected $resourceMetadataCollectionFactory;

    protected function getInputClass(string $class, array $context = []): ?string
    {
        if (!$this->resourceMetadataCollectionFactory) {
            return $context['input']['class'] ?? null;
        }

        if (null !== ($context['input']['class'] ?? null)) {
            return $context['input']['class'];
        }

        $operation = $context['operation'] ?? null;
        if (!$operation) {
            try {
                $operation = $this->resourceMetadataCollectionFactory->create($class)->getOperation($context['operation_name'] ?? null);
            } catch (OperationNotFoundException|ResourceClassNotFoundException $e) {
                return null;
            }
        }

        return $operation ? $operation->getInput()['class'] ?? null : null;
    }

    protected function getOutputClass(string $class, array $context = []): ?string
    {
        if (!$this->resourceMetadataCollectionFactory) {
            return $context['output']['class'] ?? null;
        }

        if (null !== ($context['output']['class'] ?? null)) {
            return $context['output']['class'];
        }

        $operation = $context['operation'] ?? null;
        if (null === $operation) {
            try {
                $operation = $this->resourceMetadataCollectionFactory->create($class)->getOperation($context['operation_name'] ?? null);
            } catch (OperationNotFoundException|ResourceClassNotFoundException $e) {
                return null;
            }
        }

        return $operation ? $operation->getOutput()['class'] ?? null : null;
    }
}
