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

namespace ApiPlatform\GraphQl\Metadata;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingExceptionInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * This factory runs in the ResolverFactory and is used to find out a Relay node's operation.
 */
final class RuntimeOperationMetadataFactory implements OperationMetadataFactoryInterface
{
    public function __construct(private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, private readonly RouterInterface $router)
    {
    }

    public function create(string $uriTemplate, array $context = []): ?Operation
    {
        try {
            $parameters = $this->router->match($uriTemplate);
        } catch (RoutingExceptionInterface $e) {
            throw new InvalidArgumentException(\sprintf('No route matches "%s".', $uriTemplate), $e->getCode(), $e);
        }

        if (!isset($parameters['_api_resource_class'])) {
            throw new InvalidArgumentException(\sprintf('The route "%s" is not an API route, it has no resource class in the defaults.', $uriTemplate));
        }

        foreach ($this->resourceMetadataCollectionFactory->create($parameters['_api_resource_class']) as $resource) {
            foreach ($resource->getGraphQlOperations() ?? [] as $operation) {
                if ($operation instanceof Query && !$operation->getResolver()) {
                    return $operation;
                }
            }
        }

        throw new InvalidArgumentException(\sprintf('No operation found for id "%s".', $uriTemplate));
    }
}
