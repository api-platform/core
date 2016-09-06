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
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Util\ClassInfoTrait;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingExceptionInterface;
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
    private $routeNameResolver;
    private $router;
    private $propertyAccessor;

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ItemDataProviderInterface $itemDataProvider, RouteNameResolverInterface $routeNameResolver, RouterInterface $router, PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->itemDataProvider = $itemDataProvider;
        $this->routeNameResolver = $routeNameResolver;
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
        } catch (RoutingExceptionInterface $e) {
            throw new InvalidArgumentException(sprintf('No route matches "%s".', $iri), $e->getCode(), $e);
        }

        if (!isset($parameters['_api_resource_class']) || !isset($parameters['id'])) {
            throw new InvalidArgumentException(sprintf('No resource associated to "%s".', $iri));
        }

        if ($item = $this->itemDataProvider->getItem($parameters['_api_resource_class'], $parameters['id'], null, $fetchData)) {
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
        $routeName = $this->routeNameResolver->getRouteName($resourceClass, false);

        $identifiers = $this->generateIdentifiersUrl($this->getIdentifiersFromItem($item));

        return $this->router->generate($routeName, ['id' => implode(';', $identifiers)], $referenceType);
    }

    /**
     * {@inheritdoc}
     */
    public function getIriFromResourceClass(string $resourceClass, int $referenceType = UrlGeneratorInterface::ABS_PATH) : string
    {
        try {
            return $this->router->generate($this->routeNameResolver->getRouteName($resourceClass, true), [], $referenceType);
        } catch (RoutingExceptionInterface $e) {
            throw new InvalidArgumentException(sprintf('Unable to generate an IRI for "%s".', $resourceClass), $e->getCode(), $e);
        }
    }

    /**
     * Find identifiers from an Item (Object).
     *
     * @param object $item
     *
     * @throws RuntimeException
     *
     * @return array
     */
    private function getIdentifiersFromItem($item) : array
    {
        $identifiers = [];
        $resourceClass = $this->getObjectClass($item);

        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName);

            $identifier = $propertyMetadata->isIdentifier();
            if (null === $identifier || false === $identifier) {
                continue;
            }

            $identifiers[$propertyName] = $this->propertyAccessor->getValue($item, $propertyName);

            if (!is_object($identifiers[$propertyName])) {
                continue;
            }

            $relatedResourceClass = $this->getObjectClass($identifiers[$propertyName]);
            $relatedItem = $identifiers[$propertyName];

            unset($identifiers[$propertyName]);

            foreach ($this->propertyNameCollectionFactory->create($relatedResourceClass) as $relatedPropertyName) {
                $propertyMetadata = $this->propertyMetadataFactory->create($relatedResourceClass, $relatedPropertyName);

                if ($propertyMetadata->isIdentifier()) {
                    $identifiers[$propertyName] = $this->propertyAccessor->getValue($relatedItem, $relatedPropertyName);
                }
            }

            if (empty($identifiers[$propertyName])) {
                throw new RuntimeException(sprintf('%s identifiers cannot be found', $resourceClass));
            }
        }

        return $identifiers;
    }

    /**
     * Generate the identifier url.
     *
     * @param array $identifiers
     *
     * @return string[]
     */
    private function generateIdentifiersUrl(array $identifiers) : array
    {
        if (1 === count($identifiers)) {
            return [rawurlencode(array_values($identifiers)[0])];
        }

        foreach ($identifiers as $name => $value) {
            $identifiers[$name] = sprintf('%s=%s', $name, $value);
        }

        return $identifiers;
    }
}
