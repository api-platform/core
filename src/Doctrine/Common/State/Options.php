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

namespace ApiPlatform\Doctrine\Common\State;

use ApiPlatform\State\OptionsInterface;

class Options implements OptionsInterface
{
    /**
     * @param mixed $handleLinks             experimental callable, typed mixed as we may want a service name in the future
     * @param mixed $toResourceTransformer   experimental callable, typed mixed as we may want a service name in the future
     * @param mixed $fromResourceTransformer experimental callable, typed mixed as we may want a service name in the future
     */
    public function __construct(
        protected mixed $handleLinks = null,
        protected mixed $toResourceTransformer = null,
        protected mixed $fromResourceTransformer = null,
    ) {
    }

    public function getHandleLinks(): mixed
    {
        return $this->handleLinks;
    }

    public function withHandleLinks(mixed $handleLinks): self
    {
        $self = clone $this;
        $self->handleLinks = $handleLinks;

        return $self;
    }

    public function getToResourceTransformer(): mixed
    {
        return $this->toResourceTransformer;
    }

    public function withToResourceTransformer(mixed $toResourceTransformer): self
    {
        $self = clone $this;
        $self->toResourceTransformer = $toResourceTransformer;

        return $self;
    }

    public function getFromResourceTransformer(): mixed
    {
        return $this->fromResourceTransformer;
    }

    public function withFromResourceTransformer(mixed $fromResourceTransformer): self
    {
        $self = clone $this;
        $self->fromResourceTransformer = $fromResourceTransformer;

        return $self;
    }
}
