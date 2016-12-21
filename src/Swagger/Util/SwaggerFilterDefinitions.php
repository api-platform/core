<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Swagger\Util;

use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;

class SwaggerFilterDefinitions
{
    private $resourceMetadataFactory;
    private $filterCollection;
    private $typeResolver;

    /**
     * SwaggerFilterDefinitions constructor.
     *
     * @param ResourceMetadataFactoryInterface $resourceMetadataFactory
     * @param SwaggerTypeResolver              $typeResolver
     * @param FilterCollection                 $filterCollection
     */
    public function __construct(
        ResourceMetadataFactoryInterface $resourceMetadataFactory,
        SwaggerTypeResolver $typeResolver,
        FilterCollection $filterCollection = null
    ) {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->filterCollection = $filterCollection;
        $this->typeResolver = $typeResolver;
    }

    public function get(array $operationData): array
    {
        if (null === $this->filterCollection) {
            return [];
        }

        if (!SwaggerOperationDataGuard::check($operationData)) {
            throw  new InvalidArgumentException('invalid $operationData argument ');
        }
        $parameters = [];

        $resourceMetadata = $this->resourceMetadataFactory->create($operationData['resourceClass']);
        $resourceFilters = $resourceMetadata->getCollectionOperationAttribute(
            $operationData['operationName'], 'filters', [], true
        );
        foreach ($this->filterCollection as $filterName => $filter) {
            if (!in_array($filterName, $resourceFilters)) {
                continue;
            }

            foreach ($filter->getDescription($operationData['resourceClass']) as $name => $data) {
                $parameter = [
                    'name' => $name,
                    'in' => 'query',
                    'required' => $data['required'],
                ];
                $parameter += $this->typeResolver->resolve($data['type'], false);

                if (isset($data['swagger'])) {
                    $parameter = $data['swagger'] + $parameter;
                }

                $parameters[] = $parameter;
            }
        }

        return $parameters;
    }
}
