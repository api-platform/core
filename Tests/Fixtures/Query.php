<?php

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
