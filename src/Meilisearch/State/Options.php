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

namespace ApiPlatform\Meilisearch\State;

use ApiPlatform\State\OptionsInterface;

class Options implements OptionsInterface
{
    public function __construct(
        protected ?string $index = null,
        protected ?string $primaryKey = null,
    ) {
    }

    public function getIndex(): ?string
    {
        return $this->index;
    }

    public function withIndex(?string $index): self
    {
        $self = clone $this;
        $self->index = $index;

        return $self;
    }

    public function getPrimaryKey(): ?string
    {
        return $this->primaryKey;
    }

    public function withPrimaryKey(?string $primaryKey): self
    {
        $self = clone $this;
        $self->primaryKey = $primaryKey;

        return $self;
    }
}
