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

namespace ApiPlatform\Core\Metadata\Resource\Factory;

use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

/**
 * Creates or completes operations.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class OperationResourceMetadataFactory implements ResourceMetadataFactoryInterface
{
    private $decorated;
    private $formats;

    public function __construct(ResourceMetadataFactoryInterface $decorated, array $formats = [])
    {
        $this->decorated = $decorated;
        $this->formats = $formats;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadata
    {
        $resourceMetadata = $this->decorated->create($resourceClass);
        $reflectionClass = new \ReflectionClass($resourceClass);
        $isAbstract = $reflectionClass->isAbstract();

        if (null === $resourceMetadata->getCollectionOperations()) {
            $resourceMetadata = $resourceMetadata->withCollectionOperations($this->createOperations(
                $isAbstract ? ['GET'] : ['GET', 'POST']
            ));
        }

        if (null === $resourceMetadata->getItemOperations()) {
            $methods = ['GET', 'DELETE'];

            if (!$isAbstract) {
                $methods[] = 'PUT';

                if (isset($this->formats['jsonapi'])) {
                    $methods[] = 'PATCH';
                }
            }

            $resourceMetadata = $resourceMetadata->withItemOperations($this->createOperations($methods));
        }

        return $resourceMetadata;
    }

    private function createOperations(array $methods): array
    {
        $operations = [];
        foreach ($methods as $method) {
            $operations[strtolower($method)] = ['method' => $method];
        }

        return $operations;
    }
}
