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

namespace ApiPlatform\OpenApi\Attributes;

use ApiPlatform\OpenApi\Model\PathItem;

class Webhook
{
    public function __construct(
        protected string $name,
        protected ?PathItem $pathItem = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function withName(string $name): self
    {
        $self = clone $this;
        $self->name = $name;

        return $self;
    }

    public function getPathItem(): ?PathItem
    {
        return $this->pathItem;
    }

    public function withPathItem(PathItem $pathItem): self
    {
        $self = clone $this;
        $self->pathItem = $pathItem;

        return $self;
    }
}
