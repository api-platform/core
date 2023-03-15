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
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class ControllerAttributeResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    /**
     * @param array{controller: string, method: string, parameter: string} $controllerClass
     */
    public function __construct(private array $controllerClass = [], private readonly ?ResourceMetadataCollectionFactoryInterface $decorated = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = new ResourceMetadataCollection($resourceClass, []);

        if ($this->decorated) {
            $resourceMetadataCollection = $this->decorated->create($resourceClass);
        }

        if (!$routes = $this->controllerClass[$resourceClass] ?? null) {
            return $resourceMetadataCollection;
        }

        foreach ($routes as $attribute) {
            $reflectionController = new \ReflectionClass($attribute['controller']);
            $reflectionMethod = $reflectionController->getMethod($attribute['method']);
            foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
                if ($reflectionParameter->getName() !== $attribute['parameter']) {
                    continue;
                }

                $attributes = $reflectionParameter->getAttributes(ApiResource::class, \ReflectionAttribute::IS_INSTANCEOF);

                if ($attributes[0] ?? false) {
                    $apiResource = $attributes[0]->newInstance();
                    break;
                }
            }

            $shortName = (false !== $pos = strrpos($resourceClass, '\\')) ? substr($resourceClass, $pos + 1) : $resourceClass;
            $apiResource = $apiResource->withClass($resourceClass)->withShortName($shortName);

            if (!$apiResource->getOperations()) {
                $apiResource = $apiResource->withOperations(new Operations([(new HttpOperation(routeName: $attribute['route_name'], name: $attribute['route_name']))->withResource($apiResource)]));
            }

            $resourceMetadataCollection[] = $apiResource;
        }

        return $resourceMetadataCollection;
    }
}
