<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\Bridge\Doctrine\Orm;

class ItemExtensions implements \IteratorAggregate
{
    private $itemExtensions;

    public function __construct(array $itemExtensions = [])
    {
        $this->itemExtensions = $itemExtensions;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->itemExtensions);
    }
}
