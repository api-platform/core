<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Metadata\Property;

/**
 * A collection of properties for a given resource.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class CollectionMetadata implements \IteratorAggregate, \Countable
{
    /**
     * @var string[]
     */
    private $properties;

    /**
     * @param string[] $properties
     */
    public function __construct(array $properties = [])
    {
        $this->properties = $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->properties);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->properties);
    }
}
