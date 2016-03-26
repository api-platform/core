<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Symfony\Routing;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ItemDataProviderInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Util\ClassInfoTrait;
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

    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $itemDataProvider;
    private $router;
    private $propertyAccessor;

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ItemDataProviderInterface $itemDataProvider, RouterInterface $router, PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->itemDataProvider = $itemDataProvider;
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

        if ($item = $this->itemDataProvider->getItem($parameters['_resource_class'], $parameters['id'], $fetchData)) {
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
        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName);

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
     * @throws InvalidArgumentException
     *
     * @return string
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
