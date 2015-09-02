<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Bridge\Symfony\Routing;

use Dunglas\ApiBundle\Api\IriConverterInterface;
use Dunglas\ApiBundle\Api\UrlGeneratorInterface;
use Dunglas\ApiBundle\Exception\InvalidArgumentException;
use Dunglas\ApiBundle\Metadata\Property\Factory\CollectionMetadataFactoryInterface;
use Dunglas\ApiBundle\Metadata\Property\Factory\ItemMetadataFactoryInterface;
use Dunglas\ApiBundle\Api\DataProviderInterface;
use Dunglas\ApiBundle\Util\ClassInfoTrait;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class IriConverter implements IriConverterInterface
{
    use ClassInfoTrait;

    /**
     * @var CollectionMetadataFactoryInterface
     */
    private $collectionMetadataFactory;

    /**
     * @var ItemMetadataFactoryInterface
     */
    private $itemMetadataFactory;

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

    public function __construct(CollectionMetadataFactoryInterface $collectionMetadataFactory, ItemMetadataFactoryInterface $itemMetadataFactory, DataProviderInterface $dataProvider, RouterInterface $router, PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->collectionMetadataFactory = $collectionMetadataFactory;
        $this->itemMetadataFactory = $itemMetadataFactory;
        $this->dataProvider = $dataProvider;
        $this->router = $router;
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
    }

    /**
     * {@inheritdoc}
     */
    public function getItemFromIri(string $iri, bool $fetchData = false)
    {
        try {
            $parameters = $this->router->match($iri);
        } catch (ExceptionInterface $exception) {
            throw new InvalidArgumentException(sprintf('No route matches "%s".', $iri), $exception->getCode(), $exception);
        }

        if (!isset($parameters['_resource_class']) || !isset($parameters['id'])) {
            throw new InvalidArgumentException(sprintf('No resource associated to "%s".', $iri));
        }

        if ($item = $this->dataProvider->getItem($parameters['_resource_class'], $parameters['id'], $fetchData)) {
            return $item;
        }

        throw new InvalidArgumentException(sprintf('Item not found for "%s".', $iri));
    }

    /**
     * {@inheritdoc}
     */
    public function getIriFromItem($item, int $referenceType = UrlGeneratorInterface::ABS_PATH) : string
    {
        $resourceClass = $this->getObjectClass($item);
        $routeName = $this->getRouteName($resourceClass, false);

        $identifierValues = [];
        foreach ($this->collectionMetadataFactory->create($resourceClass) as $propertyName) {
            $propertyMetadata = $this->itemMetadataFactory->create($resourceClass, $propertyName);

            if ($propertyMetadata->isIdentifier()) {
                $identifierValues[] = $this->propertyAccessor->getValue($item, $propertyName);
            }
        }

        return $this->router->generate($routeName, ['id' => implode('-', $identifierValues)], $referenceType);
    }

    /**
     * {@inheritdoc}
     */
    public function getIriFromResourceClass(string $resourceClass, int $referenceType = UrlGeneratorInterface::ABS_PATH) : string
    {
        try {
            return $this->router->generate($this->getRouteName($resourceClass, true), [], $referenceType);
        } catch (ExceptionInterface $e) {
            throw new InvalidArgumentException(sprintf('Unable to generate an IRI for "%s".', $resourceClass), $e->getCode(), $e);
        }
    }

    /**
     * Finds the route name for this resource.
     *
     * @param string $resourceClass
     * @param bool   $collection
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    private function getRouteName(string $resourceClass, bool $collection) : string
    {
        $operationType = $collection ? 'collection' : 'item';

        foreach ($this->router->getRouteCollection()->all() as $routeName => $route) {
            $currentResourceClass = $route->getDefault('_resource_class');
            $operation = $route->getDefault(sprintf('_%s_operation_name', $operationType));
            $methods = $route->getMethods();

            if ($resourceClass === $currentResourceClass && null !== $operation && (empty($methods) || in_array('GET', $methods))) {
                $found = true;
                break;
            }
        }

        if (!isset($found)) {
            throw new InvalidArgumentException(sprintf('No route associated with the type "%s".', $resourceClass));
        }

        return $routeName;
    }
}
