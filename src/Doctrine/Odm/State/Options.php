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

namespace ApiPlatform\Doctrine\Odm\State;

use ApiPlatform\State\OptionsInterface;

class Options implements OptionsInterface
{
    /**
     * @param mixed $handleLinks experimental callable, typed mixed as we may want a service name in the future
     *
     * @see LinksHandlerInterface
     */
    public function __construct(
        protected ?string $documentClass = null,
        protected mixed $handleLinks = null,
    ) {
    }

    public function getDocumentClass(): ?string
    {
        return $this->documentClass;
    }

    public function withDocumentClass(?string $documentClass): self
    {
        $self = clone $this;
        $self->documentClass = $documentClass;

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
