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

use ArrayObject;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * A collection of {@see Resource} classes.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class Resources extends \ArrayObject
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
     *
     * @throws \InvalidArgumentException
     */
    public function append($value)
    {
        if (!($value instanceof Resource)) {
            throw new \InvalidArgumentException('Only instances of Dunglas\JsonLdApiBundle\Resource can be appended.');
        }

        $entityClass = $value->getEntityClass();
        if (isset($this->entityClassIndex[$entityClass])) {
            throw new \InvalidArgumentException(sprintf('A Resource class already exists for "%s".', $entityClass));
        }

        $shortName = $value->getShortName();
        if (isset($this->shortNameIndex[$shortName])) {
            throw new \InvalidArgumentException(sprintf('A Resource class with the short name "%s" already exists.', $shortName));
        }

        parent::append($value);
        $value->setResources($this);

        $this->entityClassIndex[$entityClass] = $value;
        $this->shortNameIndex[$shortName] = $value;
    }

    /**
     * Gets the Resource instance associated with the given entity class or null if not found.
     *
     * @param string $entityClass
     *
     * @return Resource|null
     */
    public function getResourceForEntity($entityClass)
    {
        return isset($this->entityClassIndex[$entityClass]) ? $this->entityClassIndex[$entityClass] : null;
    }

    /**
     * Gets the Resource instance associated with the given short name or null if not found.
     *
     * @param string $shortName
     *
     * @return Resource|null
     */
    public function getResourceForShortName($shortName)
    {
        return isset($this->shortNameIndex[$shortName]) ? $this->shortNameIndex[$shortName] : null;
    }

    /**
     * Gets the URI of a collection.
     *
     * @param Resource $resource
     *
     * @return string
     */
    public function getCollectionUri(Resource $resource)
    {
        return $this->router->generate($resource->getCollectionRoute());
    }

    /**
     * Gets the URI of an item.
     *
     * @param object $object
     * @param string|null $entityClass
     *
     * @return string
     *
     * @throws \InvalidArgumentException
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

        return $this->router->generate($resource->getItemRoute(), ['id' => $this->propertyAccessor->getValue($object, 'id')]);
    }

    /**
     * Gets an item from an URI.
     *
     * @param string $uri
     *
     * @return object
     *
     * @throws \InvalidArgumentException
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

        return $resource->getManager()->getItem($parameters['id']);
    }
}
