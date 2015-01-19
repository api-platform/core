<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle;

/**
 * A collection of {@see Resource} classes.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class Resources extends \ArrayObject
{
    /**
     * @var array<string, Resource>
     */
    private $entityClassIndex = [];

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
            throw new \InvalidArgumentException(sprintf('A Resource class already exist for %s.', $entityClass));
        }

        parent::append($value);

        $this->entityClassIndex[$entityClass] = $value;
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
}
