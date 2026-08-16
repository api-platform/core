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

namespace ApiPlatform\Elasticsearch\State;

use ApiPlatform\State\OptionsInterface;

class Options implements OptionsInterface
{
    /**
     * @param mixed $handleLinks a callable that alters the ES|QL query according to the operation links (only used with QueryLanguage::Esql), see {@see \ApiPlatform\Elasticsearch\Esql\State\LinksHandlerInterface}
     */
    public function __construct(
        protected ?string $index = null,
        protected ?QueryLanguage $queryLanguage = null,
        protected mixed $handleLinks = null,
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

    public function getQueryLanguage(): ?QueryLanguage
    {
        return $this->queryLanguage;
    }

    public function withQueryLanguage(?QueryLanguage $queryLanguage): self
    {
        $self = clone $this;
        $self->queryLanguage = $queryLanguage;

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
