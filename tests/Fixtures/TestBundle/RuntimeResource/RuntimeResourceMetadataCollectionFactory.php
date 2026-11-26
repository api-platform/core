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

namespace ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Metadata\Resource\Factory\OperationDefaultsTrait;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Util\CamelCaseToSnakeCaseNameConverter;

final class RuntimeResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    use OperationDefaultsTrait;

    /**
     * @param array<string, mixed> $defaults
     */
    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $decorated,
        private readonly RuntimeResources $resources,
        private readonly string $provider,
        private readonly string $processor,
        private readonly string $filter,
        array $defaults = [],
    ) {
        $this->defaults = $defaults;
        $this->camelCaseToSnakeCaseNameConverter = new CamelCaseToSnakeCaseNameConverter();
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        if (null === $definition = $this->resources->get($resourceClass)) {
            return $this->decorated->create($resourceClass);
        }

        $collectionLinks = ['resource' => new Link(fromClass: $resourceClass, identifiers: ['resource'], parameterName: 'resource')];
        $itemLinks = $collectionLinks + ['id' => new Link(fromClass: $resourceClass, identifiers: ['id'], parameterName: 'id')];
        $collection = ['routeName' => RuntimeRouter::COLLECTION_ROUTE, 'uriTemplate' => '/runtime/'.$definition->name, 'uriVariables' => $collectionLinks];
        $item = ['routeName' => RuntimeRouter::ITEM_ROUTE, 'uriTemplate' => '/runtime/'.$definition->name.'/{id}', 'uriVariables' => $itemLinks];
        $name = static fn (string $suffix): string => RuntimeRouter::operationName($definition->name, $suffix);

        $parameters = [];
        foreach ($definition->attributes as $attribute) {
            if ($attribute->filterable) {
                $parameters[$attribute->name] = new QueryParameter(key: $attribute->name, property: $attribute->name, filter: $this->filter);
            }
        }

        $resource = $this->addGlobalDefaults(new ApiResource(
            class: $resourceClass,
            shortName: ucfirst($definition->name),
            description: $definition->label,
            security: $definition->security,
            provider: $this->provider,
            processor: $this->processor,
            collectDenormalizationErrors: true,
            extraProperties: ['openapi_use_uri_template' => true],
        ));

        $operations = [];
        foreach ([
            new Get(...$item, name: $name('get')),
            new GetCollection(...$collection, name: $name('get_collection'), parameters: new Parameters($parameters)),
            new Post(...$collection, name: $name('post'), read: false),
            new Put(...$item, name: $name('put')),
            new Patch(...$item, name: $name('patch')),
            new Delete(...$item, name: $name('delete')),
        ] as $operation) {
            [$key, $operation] = $this->getOperationWithDefaults($resource, $operation);
            $operations[$key] = $operation;
        }

        return new ResourceMetadataCollection($resourceClass, [$resource->withOperations(new Operations($operations))]);
    }
}
