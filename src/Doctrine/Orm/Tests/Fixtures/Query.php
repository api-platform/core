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

namespace ApiPlatform\Doctrine\Orm\Tests\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Replace Doctrine\ORM\Query in tests because it cannot be mocked.
 */
class Query
{
    public function getFirstResult(): ?int
    {
        return null;
    }

    public function getMaxResults(): ?int
    {
        return null;
    }

    public function setFirstResult($firstResult): self
    {
        return $this;
    }

    public function setMaxResults($maxResults): self
    {
        return $this;
    }

    public function setParameters($parameters): self
    {
        return $this;
    }

    public function getParameters()
    {
        return new ArrayCollection();
    }

    public function setCacheable($cacheable): self
    {
        return $this;
    }

    public function getHints()
    {
        return [];
    }

    public function getFetchJoinCollection()
    {
        return false;
    }

    public function getResult(): void
    {
    }
}
