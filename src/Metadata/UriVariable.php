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
final class UriVariable
{
    private $parameterName;
    private $inverseProperty;
    private $property;
    private $targetClass;
    private $identifiers;
    private $compositeIdentifier;

    public function __construct(?string $parameterName = null, ?string $inverseProperty = null, ?string $property = null, ?string $targetClass = null, ?array $identifiers = null, ?bool $compositeIdentifier = null)
    {
        // For the inverse property shortcut
        if ($parameterName && class_exists($parameterName)) {
            $this->targetClass = $parameterName;
        } else {
            $this->parameterName = $parameterName;
        }

        $this->targetClass = $targetClass;
        $this->inverseProperty = $inverseProperty;
        $this->property = $property;
        $this->identifiers = $identifiers;
        $this->compositeIdentifier = $compositeIdentifier;
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

    public function getTargetClass(): ?string
    {
        return $this->targetClass;
    }

    public function withTargetClass(string $targetClass): self
    {
        $self = clone $this;
        $self->targetClass = $targetClass;

        return $self;
    }

    public function getInverseProperty(): ?string
    {
        return $this->inverseProperty;
    }

    public function withInverseProperty(string $inverseProperty): self
    {
        $self = $this;
        $self->inverseProperty = $inverseProperty;

        return $self;
    }

    public function getProperty(): ?string
    {
        return $this->property;
    }

    public function withProperty(string $property): self
    {
        $self = clone $this;
        $self->property = $property;

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

    public function withUriVariable(self $uriVariable): self
    {
        $self = clone $this;

        if (!$self->getProperty() && ($property = $uriVariable->getProperty())) {
            $self->property = $property;
        }

        if (!$self->getCompositeIdentifier() && ($compositeIdentifier = $uriVariable->getCompositeIdentifier())) {
            $self->compositeIdentifier = $compositeIdentifier;
        }

        if (!$self->getTargetClass() && ($targetClass = $uriVariable->getTargetClass())) {
            $self->targetClass = $targetClass;
        }

        if (!$self->getIdentifiers() && ($identifiers = $uriVariable->getIdentifiers())) {
            $self->identifiers = $identifiers;
        }

        if (!$self->getInverseProperty() && ($inverseProperty = $uriVariable->getInverseProperty())) {
            $self->inverseProperty = $inverseProperty;
        }

        if (!$self->getParameterName() && ($parameterName = $uriVariable->getParameterName())) {
            $self->parameterName = $parameterName;
        }

        return $self;
    }
}
