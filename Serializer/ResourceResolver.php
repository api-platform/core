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
     * @var \Dunglas\JsonLdApiBundle\JsonLd\ResourceCollectionInterface
     */
    private $resourceCollection;

    /**
     * Guesses the associated resource.
     *
     * @param mixed      $type
     * @param array|null $context
     *
     * @return \Dunglas\JsonLdApiBundle\JsonLd\ResourceInterface
     *
     * @throws InvalidArgumentException
     */
    public function guessResource($type, array $context = null)
    {
        if (isset($context['resource'])) {
            return $context['resource'];
        }

        if (is_object($type)) {
            $type = get_class($type);
        }

        if (!is_string($type)) {
            $type = gettype($type);
        }

        if ($resource = $this->resourceCollection->getResourceForEntity($type)) {
            return $resource;
        }

        throw new InvalidArgumentException(
            sprintf('Cannot find a resource object for type "%s".', $type)
        );
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
}
