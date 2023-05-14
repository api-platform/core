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

namespace ApiPlatform\Doctrine\Orm\State;

use ApiPlatform\State\OptionsInterface;

class Options implements OptionsInterface
{
    public function __construct(
        protected ?string $entityClass = null,
    ) {
    }

    public function getEntityClass(): ?string
    {
        return $this->entityClass;
    }

    public function withEntityClass(?string $entityClass): self
    {
        $self = clone $this;
        $self->entityClass = $entityClass;

        return $self;
    }
}
