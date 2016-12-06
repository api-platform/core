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

use Dunglas\ApiBundle\Exception\InvalidArgumentException;
use Dunglas\ApiBundle\Util\ClassInfoTrait;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ResourceCollection extends \ArrayObject implements ResourceCollectionInterface
{
    use ClassInfoTrait;

    /**
     * @var array
     */
    private $entityClassIndex = [];
    /**
     * @var array
     */
    private $shortNameIndex = [];

    /**
     * {@inheritdoc}
     */
    public function init(array $resources)
    {
        foreach ($resources as $resource) {
            $entityClass = $resource->getEntityClass();
            if (isset($this->entityClassIndex[$entityClass])) {
                throw new InvalidArgumentException(sprintf('A Resource class already exists for "%s".', $entityClass));
            }

            $shortName = $resource->getShortName();
            if (isset($this->shortNameIndex[$shortName])) {
                throw new InvalidArgumentException(sprintf('A Resource class with the short name "%s" already exists.', $shortName));
            }

            $this->append($resource);

            $this->entityClassIndex[$entityClass] = $resource;
            $this->shortNameIndex[$shortName] = $resource;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceForEntity($entityClass)
    {
        if (is_object($entityClass)) {
            $entityClass = $this->getObjectClass($entityClass);
        }

        if(isset($this->entityClassIndex[$entityClass]))
        {
            $resource =  $this->entityClassIndex[$entityClass];
        }
        elseif(isset($this->entityClassIndex[get_parent_class($entityClass)]))
        {
            $resource = $this->entityClassIndex[get_parent_class($entityClass)];
        }

        return isset($resource) ? $resource : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceForShortName($shortName)
    {
        return isset($this->shortNameIndex[$shortName]) ? $this->shortNameIndex[$shortName] : null;
    }
}
