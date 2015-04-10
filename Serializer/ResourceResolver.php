<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\Serializer;

use Dunglas\JsonLdApiBundle\JsonLd\ResourceCollectionInterface;
use Dunglas\JsonLdApiBundle\JsonLd\ResourceInterface;
use Doctrine\Common\Util\ClassUtils;
use PropertyInfo\Type;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

/**
 * This class helps to guess which resource is associated with a given object.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
trait ResourceResolver
{
    /**
     * @var ResourceCollectionInterface
     */
    private $resourceCollection;

    /**
     * Guesses the associated resource.
     *
     * @param mixed      $object
     * @param array|null $context
     * @param bool       $strict
     *
     * @return ResourceInterface
     *
     * @throws InvalidArgumentException
     */
    public function guessResource($object, array $context = null, $strict = false)
    {
        $type = $object;

        if (is_object($type)) {
            $type = $this->getObjectClass($type);
            $isObject = true;
        }

        if (!is_string($type)) {
            $type = gettype($type);
        }

        if (isset($context['resource'])) {
            $resource = $context['resource'];
        } else {
            $resource = $this->resourceCollection->getResourceForEntity($type);
        }

        if (null === $resource) {
            throw new InvalidArgumentException(
                sprintf('Cannot find a resource object for type "%s".', $type)
            );
        }

        if ($strict && isset($isObject) && $resource->getEntityClass() !== $type) {
            throw new InvalidArgumentException(
                sprintf('No resource found for object of type "%s"', $type)
            );
        }

        return $resource;
    }

    /**
     * Returns the class if a resource is associated with it.
     *
     * @param Type $type
     *
     * @return string|null
     */
    public function getClassHavingResource(Type $type)
    {
        if (
            'object' === $type->getType() &&
            ($class = $type->getClass()) &&
            $this->resourceCollection->getResourceForEntity($class)
        ) {
            return $class;
        }
    }

    /**
     * Get class name of the given object.
     *
     * @param object $object
     *
     * @return string
     */
    private function getObjectClass($object)
    {
        return class_exists('Doctrine\Common\Util\ClassUtils') ? ClassUtils::getClass($object) : get_class($object);
    }
}
