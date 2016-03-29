<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Api;

use Dunglas\ApiBundle\Util\ClassInfoTrait;
use PropertyInfo\Type;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

/**
 * This class helps to guess which resource is associated with a given object.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
trait ResourceResolverTrait
{
    use ClassInfoTrait;

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
     * @throws InvalidArgumentException
     *
     * @return ResourceInterface
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
            if (is_subclass_of($type, $resource->getEntityClass())) {
                $resource = $this->resourceCollection->getResourceForEntity($type);
                if (null !== $resource) {
                    return $resource;
                }
            }

            throw new InvalidArgumentException(
                sprintf('No resource found for object of type "%s"', $type)
            );
        }

        return $resource;
    }

    /**
     * Returns the resource associated with the given type or null.
     *
     * @param Type $type
     *
     * @return ResourceInterface|null
     */
    public function getResourceFromType(Type $type)
    {
        if (
            'object' === $type->getType() &&
            ($class = $type->getClass()) &&
            $resource = $this->resourceCollection->getResourceForEntity($class)
        ) {
            return $resource;
        }
    }
}
