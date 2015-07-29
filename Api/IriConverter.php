<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Api;

use Dunglas\ApiBundle\Exception\InvalidArgumentException;
use Dunglas\ApiBundle\Mapping\Factory\ClassMetadataFactoryInterface;
use Dunglas\ApiBundle\Model\DataProviderInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class IriConverter implements IriConverterInterface
{
    /**
     * @var ResourceCollectionInterface
     */
    private $resourceCollection;
    /**
     * @var DataProviderInterface
     */
    private $dataProvider;
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;
    /**
     * @var \SplObjectStorage
     */
    private $routeCache;
    /**
     * @var ClassMetadataFactoryInterface
     */
    private $classMetadataFactory;

    public function __construct(
        ResourceCollectionInterface $resourceCollection,
        DataProviderInterface $dataProvider,
        ClassMetadataFactoryInterface $classMetadataFactory,
        RouterInterface $router,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->resourceCollection = $resourceCollection;
        $this->dataProvider = $dataProvider;
        $this->classMetadataFactory = $classMetadataFactory;
        $this->router = $router;
        $this->propertyAccessor = $propertyAccessor;
        $this->routeCache = new \SplObjectStorage();
    }

    /**
     * {@inheritdoc}
     */
    public function getItemFromIri($iri, $fetchData = false)
    {
        try {
            $parameters = $this->router->match($iri);
        } catch (ExceptionInterface $e) {
            throw new InvalidArgumentException(sprintf('No route matches "%s".', $iri), $e->getCode(), $e);
        }

        if (
            !isset($parameters['_resource']) ||
            !isset($parameters['id']) ||
            !($resource = $this->resourceCollection->getResourceForShortName($parameters['_resource']))
        ) {
            throw new InvalidArgumentException(sprintf('No resource associated to "%s".', $iri));
        }

        if ($item = $this->dataProvider->getItem($resource, $parameters['id'], $fetchData)) {
            return $item;
        }

        throw new InvalidArgumentException(sprintf('Item not found for "%s".', $iri));
    }

    /**
     * {@inheritdoc}
     */
    public function getIriFromItem($item, $referenceType = RouterInterface::ABSOLUTE_PATH)
    {
        if ($resource = $this->resourceCollection->getResourceForEntity($item)) {
            $identifierName = $this->getIdentifierNameFromResource($resource);

            return $this->router->generate(
                $this->getRouteName($resource, 'item'),
                ['id' => $this->propertyAccessor->getValue($item, $identifierName)],
                $referenceType
            );
        }

        throw new InvalidArgumentException(sprintf('No resource associated with the type "%s".', get_class($item)));
    }

    /**
     * {@inheritdoc}
     */
    public function getIriFromResource(ResourceInterface $resource, $referenceType = RouterInterface::ABSOLUTE_PATH)
    {
        try {
            return $this->router->generate($this->getRouteName($resource, 'collection'), [], $referenceType);
        } catch (ExceptionInterface $e) {
            throw new InvalidArgumentException(sprintf('Unable to generate an IRI for "%s".', $resource->getShortName()), $e->getCode(), $e);
        }
    }

    /**
     * Gets the route name related to a resource.
     *
     * @param ResourceInterface $resource
     *
     * @return string|null
     */
    private function getRouteName(ResourceInterface $resource, $prefix)
    {
        if (!$this->routeCache->contains($resource)) {
            $this->routeCache[$resource] = [];
        }

        $key = $prefix.'RouteName';

        if (isset($this->routeCache[$resource][$key])) {
            return $this->routeCache[$resource][$key];
        }

        $operations = 'item' === $prefix ? $resource->getItemOperations() : $resource->getCollectionOperations();
        foreach ($operations as $operation) {
            $methods = $operation->getRoute()->getMethods();
            if (empty($methods) || in_array('GET', $methods)) {
                $data = $this->routeCache[$resource];
                $data[$key] = $operation->getRouteName();
                $this->routeCache[$resource] = $data;

                return $data[$key];
            }
        }
    }

    /**
     * Gets the identifier name.
     *
     * @param ResourceInterface $resource
     *
     * @return string
     */
    private function getIdentifierNameFromResource(ResourceInterface $resource)
    {
        $classMetadata = $this->classMetadataFactory->getMetadataFor(
            $resource->getEntityClass(),
            $resource->getNormalizationGroups(),
            $resource->getDenormalizationGroups(),
            $resource->getValidationGroups()
        );

        return $classMetadata->getIdentifierName();
    }
}
