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

namespace ApiPlatform\Metadata;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::TARGET_PARAMETER)]
final class Link
{
    private $parameterName;
    private $fromProperty;
    private $toProperty;
    private $fromClass;
    private $toClass;
    private $identifiers;
    private $compositeIdentifier;
    private $expandedValue;

    public function __construct(?string $parameterName = null, ?string $fromProperty = null, ?string $toProperty = null, ?string $fromClass = null, ?string $toClass = null, ?array $identifiers = null, ?bool $compositeIdentifier = null, ?string $expandedValue = null)
    {
        // For the inverse property shortcut
        if ($parameterName && class_exists($parameterName)) {
            $this->fromClass = $parameterName;
        } else {
            $this->parameterName = $parameterName;
        }

        $this->fromClass = $fromClass;
        $this->toClass = $toClass;
        $this->fromProperty = $fromProperty;
        $this->toProperty = $toProperty;
        $this->identifiers = $identifiers;
        $this->compositeIdentifier = $compositeIdentifier;
        $this->expandedValue = $expandedValue;
    }

    public function getParameterName(): ?string
    {
        return $this->parameterName;
    }

    public function withParameterName(string $parameterName): self
    {
        $self = clone $this;
        $self->parameterName = $parameterName;

        return $self;
    }

    public function getFromClass(): ?string
    {
        return $this->fromClass;
    }

    public function withFromClass(string $fromClass): self
    {
        $self = clone $this;
        $self->fromClass = $fromClass;

        return $self;
    }

    public function getToClass(): ?string
    {
        return $this->toClass;
    }

    public function withToClass(string $toClass): self
    {
        $self = clone $this;
        $self->toClass = $toClass;

        return $self;
    }

    public function getFromProperty(): ?string
    {
        return $this->fromProperty;
    }

    public function withFromProperty(string $fromProperty): self
    {
        $self = $this;
        $self->fromProperty = $fromProperty;

        return $self;
    }

    public function getToProperty(): ?string
    {
        return $this->toProperty;
    }

    public function withToProperty(string $toProperty): self
    {
        $self = clone $this;
        $self->toProperty = $toProperty;

        return $self;
    }

    public function getIdentifiers(): ?array
    {
        return $this->identifiers;
    }

    public function withIdentifiers(array $identifiers): self
    {
        $self = clone $this;
        $self->identifiers = $identifiers;

        return $self;
    }

    public function getCompositeIdentifier(): ?bool
    {
        return $this->compositeIdentifier;
    }

    public function withCompositeIdentifier(bool $compositeIdentifier): self
    {
        $self = clone $this;
        $self->compositeIdentifier = $compositeIdentifier;

        return $self;
    }

    public function getExpandedValue(): ?string
    {
        return $this->expandedValue;
    }

    public function withExpandedValue(string $expandedValue): self
    {
        $self = clone $this;
        $self->expandedValue = $expandedValue;

        return $self;
    }

    public function withLink(self $link): self
    {
        $self = clone $this;

        if (!$self->getToProperty() && ($toProperty = $link->getToProperty())) {
            $self->toProperty = $toProperty;
        }

        if (!$self->getCompositeIdentifier() && ($compositeIdentifier = $link->getCompositeIdentifier())) {
            $self->compositeIdentifier = $compositeIdentifier;
        }

        if (!$self->getFromClass() && ($fromClass = $link->getFromClass())) {
            $self->fromClass = $fromClass;
        }

        if (!$self->getToClass() && ($toClass = $link->getToClass())) {
            $self->toClass = $toClass;
        }

        if (!$self->getIdentifiers() && ($identifiers = $link->getIdentifiers())) {
            $self->identifiers = $identifiers;
        }

        if (!$self->getFromProperty() && ($fromProperty = $link->getFromProperty())) {
            $self->fromProperty = $fromProperty;
        }

        if (!$self->getParameterName() && ($parameterName = $link->getParameterName())) {
            $self->parameterName = $parameterName;
        }

        if (!$self->getExpandedValue() && ($expandedValue = $link->getExpandedValue())) {
            $self->expandedValue = $expandedValue;
        }

        return $self;
    }
}
