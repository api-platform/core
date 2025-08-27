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

namespace ApiPlatform\Doctrine\Common\Filter;

use ApiPlatform\Metadata\Exception\RuntimeException;
use Doctrine\Persistence\ManagerRegistry;

trait ManagerRegistryAwareTrait
{
    private ?ManagerRegistry $managerRegistry = null;

    public function hasManagerRegistry(): bool
    {
        return $this->managerRegistry instanceof ManagerRegistry;
    }

    public function getManagerRegistry(): ManagerRegistry
    {
        if (!$this->hasManagerRegistry()) {
            throw new RuntimeException('ManagerRegistry must be initialized before accessing it.');
        }

        return $this->managerRegistry;
    }

    public function setManagerRegistry(ManagerRegistry $managerRegistry): void
    {
        $this->managerRegistry = $managerRegistry;
    }
}
