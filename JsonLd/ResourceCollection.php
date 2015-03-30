<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\JsonLd;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ResourceCollection extends \ArrayObject implements ResourceCollectionInterface
{
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var array<string, Resource>
     */
    private $entityClassIndex = [];
    /**
     * @var array<string, Resource>
     */
    private $shortNameIndex = [];

    public function __construct(RouterInterface $router, PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->router = $router;
        $this->propertyAccessor = $propertyAccessor ? $propertyAccessor : PropertyAccess::createPropertyAccessor();
    }

    /**
     * {@inheritdoc}
     */
    public function add(ResourceInterface $resource)
    {
        $entityClass = $resource->getEntityClass();
        if (isset($this->entityClassIndex[$entityClass])) {
            throw new \InvalidArgumentException(sprintf('A Resource class already exists for "%s".', $entityClass));
        }

        $shortName = $resource->getShortName();
        if (isset($this->shortNameIndex[$shortName])) {
            throw new \InvalidArgumentException(sprintf('A Resource class with the short name "%s" already exists.', $shortName));
        }

        $this->append($resource);
        $resource->initResourceCollection($this);

        $this->entityClassIndex[$entityClass] = $resource;
        $this->shortNameIndex[$shortName] = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceForEntity($entityClass)
    {
        return isset($this->entityClassIndex[$entityClass]) ? $this->entityClassIndex[$entityClass] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceForShortName($shortName)
    {
        return isset($this->shortNameIndex[$shortName]) ? $this->shortNameIndex[$shortName] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollectionUri(ResourceInterface $resource)
    {
        return $this->router->generate($resource->getCollectionRouteName());
    }

    /**
     * {@inheritdoc}
     */
    public function getItemUri($object, $entityClass = null)
    {
        if (!$entityClass) {
            $entityClass = get_class($object);
        }

        $resource = $this->getResourceForEntity($entityClass);
        if (!$resource) {
            throw new \InvalidArgumentException(sprintf('No resource associated with the type "%s".', $entityClass));
        }

        return $this->router->generate($resource->getItemRouteName(), ['id' => $this->propertyAccessor->getValue($object, 'id')]);
    }

    /**
     * {@inheritdoc}
     */
    public function getItemFromUri($uri)
    {
        $parameters = $this->router->match($uri);
        if (
            !isset($parameters['_json_ld_resource']) ||
            !isset($parameters['id']) ||
            !($resource = $this->getResourceForShortName($parameters['_json_ld_resource']))
        ) {
            throw new \InvalidArgumentException(sprintf('No resource associated with the URI "%s".', $uri));
        }

        return $resource->getDataProvider()->getItem($parameters['id']);
    }
}
