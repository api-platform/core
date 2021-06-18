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

namespace ApiPlatform\Core\Metadata\ResourceCollection\Factory;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\ResourceCollection\ResourceCollection;
use ApiPlatform\Core\Operation\Factory\SubresourceOperationFactoryInterface;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Resource;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

final class SubresourceMetadataResourceCollectionFactory implements ResourceCollectionMetadataFactoryInterface
{
    private $decorated;
    private $subresourceOperationFactory;
    private $converter;

    public function __construct(ResourceCollectionMetadataFactoryInterface $decorated = null, SubresourceOperationFactoryInterface $subresourceOperationFactory = null)
    {
        $this->decorated = $decorated;
        $this->subresourceOperationFactory = $subresourceOperationFactory;
        $this->converter = new CamelCaseToSnakeCaseNameConverter();
    }

    public function create(string $resourceClass): ResourceCollection
    {
        $parentResourceCollection = [];
        if ($this->decorated) {
            try {
                $parentResourceCollection = $this->decorated->create($resourceClass);
            } catch (ResourceClassNotFoundException $resourceNotFoundException) {
            }
        }

        foreach ($this->subresourceOperationFactory->create($resourceClass) as $subresourceMetadata) {
            $resource = new Resource(
                uriTemplate: $subresourceMetadata['path'],
                shortName: $subresourceMetadata['shortNames'][0],
                operations: [
                    $subresourceMetadata['route_name'] => new Get(
                        uriTemplate: $subresourceMetadata['path'],
                        shortName: $subresourceMetadata['shortNames'][0],
                        identifiers: $subresourceMetadata['identifiers'],
                        defaults: $subresourceMetadata['defaults'],
                        requirements: $subresourceMetadata['requirements'],
                        options: $subresourceMetadata['options'],
                        stateless: $subresourceMetadata['stateless'],
                        host: $subresourceMetadata['host'],
                        schemes: $subresourceMetadata['schemes'],
                        condition: $subresourceMetadata['condition'],
                        class: $subresourceMetadata['resource_class'],
                        collection: $subresourceMetadata['collection'],
                    ),
                ],
                identifiers: $subresourceMetadata['identifiers'],
                defaults: $subresourceMetadata['defaults'],
                requirements: $subresourceMetadata['requirements'],
                options: $subresourceMetadata['options'],
                stateless: $subresourceMetadata['stateless'],
                host: $subresourceMetadata['host'],
                schemes: $subresourceMetadata['schemes'],
                condition: $subresourceMetadata['condition'],
                class: $subresourceMetadata['resource_class'],
            );

            if ($subresourceMetadata['controller']) { // manage null values from subresources
                $resource = $resource->withController($subresourceMetadata['controller']);
            }

            if ($resource->getIdentifiers()) {
                $resource = $resource->withIdentifiers(array_keys($resource->getIdentifiers()));
            }

            // creer des links avec le get de la resource actuelle

            $parentResourceCollection[] = $resource;

            // essayer de boucler dans les identifiers pour les transformer au bon format avec un array[2] avant de les renvoyer au resource
            // passer dans le constructeur de resource tous mes withers

            // property ??? on sait pas ce que c'est pour l'instant
            // route_name ????
        }

        return $parentResourceCollection; //new ResourceCollection([$resource]);
    }
}
