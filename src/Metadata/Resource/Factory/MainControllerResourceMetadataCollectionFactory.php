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

namespace ApiPlatform\Metadata\Resource\Factory;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class MainControllerResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    use OperationDefaultsTrait;

    public function __construct(private readonly ?ResourceMetadataCollectionFactoryInterface $decorated = null, private ?bool $useSymfonyEvents = false)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = new ResourceMetadataCollection($resourceClass);
        if ($this->decorated) {
            $resourceMetadataCollection = $this->decorated->create($resourceClass);
        }

        foreach ($resourceMetadataCollection as $i => $resource) {
            /** @var ApiResource $resource */
            $operations = $resource->getOperations() ?? new Operations();
            foreach ($resource->getOperations() as $key => $operation) {
                if ($operation->getRouteName() || $operation->getController()) {
                    continue;
                }

                if (false === $this->useSymfonyEvents) {
                    $operation = $operation->withController('api_platform.symfony.main_controller');
                    $operations->add($key, $operation);
                }
            }

            $resource = $resource->withOperations($operations);
            $resourceMetadataCollection[$i] = $resource;
        }

        return $resourceMetadataCollection;
    }
}
