<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Builder\Metadata\Resource;

/**
 * A collection of resource class names.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class CollectionMetadata implements \IteratorAggregate, \Countable
{
    private $classes;

    /**
     * @param string[] $classes
     */
    public function __construct(array $classes = [])
    {
        $this->classes = $classes;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->classes);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->classes);
    }
}
