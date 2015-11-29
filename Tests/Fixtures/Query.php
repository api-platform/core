<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\Fixtures;

/**
 * Replace Doctrine\ORM\Query in tests because it cannot be mocked.
 */
class Query
{
    public function getFirstResult()
    {
    }

    public function getMaxResults()
    {
    }
}
