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

use ApiPlatform\Doctrine\Common\State\Options as CommonOptions;
use ApiPlatform\State\OptionsInterface;

class Options extends CommonOptions implements OptionsInterface
{
    /**
     * @param string|callable $handleLinks experimental callable, typed mixed as we may want a service name in the future
     *
     * @see LinksHandlerInterface
     */
    public function __construct(
        protected ?string $entityClass = null,
        mixed $handleLinks = null,
    ) {
        parent::__construct(handleLinks: $handleLinks);
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
}
