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

use Doctrine\ORM\EntityManager;
use Dunglas\ApiBundle\Model\DataProviderInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
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
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(
        ResourceCollectionInterface $resourceCollection,
        DataProviderInterface $dataProvider,
        RouterInterface $router,
        PropertyAccessorInterface $propertyAccessor,
        EntityManager $entityManager
    ) {
        $this->resourceCollection = $resourceCollection;
        $this->dataProvider = $dataProvider;
        $this->router = $router;
        $this->propertyAccessor = $propertyAccessor;
        $this->routeCache = new \SplObjectStorage();
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemFromIri($iri, $fetchData = false)
    {
        try {
            $parameters = $this->router->match($iri);
        } catch (ResourceNotFoundException $e) {
            return;
        }

        if (
            !isset($parameters['_resource']) ||
            !isset($parameters['id']) ||
            !($resource = $this->resourceCollection->getResourceForShortName($parameters['_resource']))
        ) {
            throw new \InvalidArgumentException(sprintf('No resource associated with the IRI "%s".', $iri));
        }

        return $this->dataProvider->getItem($resource, $parameters['id'], $fetchData);
    }

    /**
     * {@inheritdoc}
     */
    public function getIriFromItem($item, $referenceType = RouterInterface::ABSOLUTE_PATH)
    {
        if ($resource = $this->resourceCollection->getResourceForEntity($item)) {
            return $this->router->generate(
                $this->getRouteName($resource, 'item'),
                ['id' => $this->propertyAccessor->getValue($item, $this->getIdentifierFromClassName(get_class($item)))],
                $referenceType
            );
        }

        throw new \InvalidArgumentException(sprintf('No resource associated with the type "%s".', get_class($item)));
    }

    /**
     * @param string $className
     *
     * @throws |Exception
     *
     * @return string
     */
    private function getIdentifierFromClassName($className)
    {
        $classMetaData = $this->entityManager->getClassMetadata($className);
        $identifierList = $classMetaData->getIdentifier();

        if (count($identifierList) != 1) {
            throw new \Exception(sprintf(
                'Entity "%s" have multiple identifier, actually we support only simple identifier', $className
            ));
        }

        return array_shift($identifierList);
    }

    /**
     * {@inheritdoc}
     */
    public function getIriFromResource(ResourceInterface $resource, $referenceType = RouterInterface::ABSOLUTE_PATH)
    {
        return $this->router->generate($this->getRouteName($resource, 'collection'), [], $referenceType);
    }

    /**
     * Gets the route name related to a resource.
     *
     * @param ResourceInterface $resource
     *
     * @return string
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
}
