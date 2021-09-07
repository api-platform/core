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

use ApiPlatform\Core\Metadata\Property\SubresourceMetadata;
use ApiPlatform\Metadata\Property\DeprecationMetadataTrait;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

/**
 * ApiProperty annotation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::TARGET_PARAMETER)]
final class ApiProperty
{
    use DeprecationMetadataTrait;

    /**
     * @var string
     */
    private $description;

    /**
     * @var bool
     */
    private $readable;

    /**
     * @var bool
     */
    private $writable;

    /**
     * @var bool
     */
    private $readableLink;

    /**
     * @var bool
     */
    private $writableLink;

    /**
     * @var bool
     */
    private $required;

    /**
     * @var bool
     */
    private $identifier;

    /**
     * @var string|int|float|bool|array|null
     */
    private $default;

    /**
     * @var string|int|float|bool|array|null
     */
    private $example;

    private $deprecationReason;
    private $fetchable;
    private $fetchEager;
    private $jsonldContext;
    private $openapiContext;
    private $push;
    private $security;
    private $securityPostDenormalize;

    /**
     * The RDF types of this property.
     *
     * @var string[]
     */
    private $types;

    /**
     * The related php types.
     *
     * @var Type[]
     */
    private $builtinTypes;

    private $schema;
    private $initializable;

    /**
     * @var array
     */
    private $extraProperties;

    /**
     * @param string                      $description
     * @param bool                        $readable
     * @param bool                        $writable
     * @param bool                        $readableLink
     * @param bool                        $writableLink
     * @param bool                        $required
     * @param bool                        $identifier
     * @param string|int|float|bool|array $default
     * @param string|int|float|bool|array $example
     * @param string                      $deprecationReason
     * @param bool                        $fetchable
     * @param bool                        $fetchEager
     * @param array                       $jsonldContext
     * @param array                       $openapiContext
     * @param bool                        $push
     * @param string                      $security
     * @param string                      $securityPostDenormalize
     * @param string[]|string             $types
     */
    public function __construct(
        ?string $description = null,
        ?bool $readable = null,
        ?bool $writable = null,
        ?bool $readableLink = null,
        ?bool $writableLink = null,
        ?bool $required = null,
        ?bool $identifier = null,

        $default = null,
        $example = null,

        ?string $deprecationReason = null,
        ?bool $fetchable = null,
        ?bool $fetchEager = null,
        ?array $jsonldContext = null,
        ?array $openapiContext = null,
        ?bool $push = null,
        ?string $security = null,
        ?string $securityPostDenormalize = null,

        $types = [],
        ?array $builtinTypes = [],
        ?array $schema = [],
        ?bool $initializable = null,

        // attributes
        ?array $extraProperties = []
    ) {
        $this->description = $description;
        $this->readable = $readable;
        $this->writable = $writable;
        $this->readableLink = $readableLink;
        $this->writableLink = $writableLink;
        $this->required = $required;
        $this->identifier = $identifier;
        $this->default = $default;
        $this->example = $example;
        $this->deprecationReason = $deprecationReason;
        $this->fetchable = $fetchable;
        $this->fetchEager = $fetchEager;
        $this->jsonldContext = $jsonldContext;
        $this->openapiContext = $openapiContext;
        $this->push = $push;
        $this->security = $security;
        $this->openapiContext = $openapiContext;
        $this->securityPostDenormalize = $securityPostDenormalize;
        $this->types = (array) $types;
        $this->builtinTypes = $builtinTypes;
        $this->schema = $schema;
        $this->initializable = $initializable;
        $this->extraProperties = $extraProperties;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function withDescription(?string $description = null): self
    {
        $self = clone $this;
        $self->description = $description;

        return $self;
    }

    public function isReadable(): ?bool
    {
        return $this->readable;
    }

    public function withReadable(?bool $readable = null): self
    {
        $self = clone $this;
        $self->readable = $readable;

        return $self;
    }

    public function isWritable(): ?bool
    {
        return $this->writable;
    }

    public function withWritable(?bool $writable = null): self
    {
        $self = clone $this;
        $self->writable = $writable;

        return $self;
    }

    public function isReadableLink(): ?bool
    {
        return $this->readableLink;
    }

    public function withReadableLink(?bool $readableLink = null): self
    {
        $self = clone $this;
        $self->readableLink = $readableLink;

        return $self;
    }

    public function isWritableLink(): ?bool
    {
        return $this->writableLink;
    }

    public function withWritableLink(?bool $writableLink = null): self
    {
        $self = clone $this;
        $self->writableLink = $writableLink;

        return $self;
    }

    public function isRequired(): ?bool
    {
        return $this->required;
    }

    public function withRequired(?bool $required = null): self
    {
        $self = clone $this;
        $self->required = $required;

        return $self;
    }

    public function isIdentifier(): ?bool
    {
        return $this->identifier;
    }

    public function withIdentifier(?bool $identifier = null): self
    {
        $self = clone $this;
        $self->identifier = $identifier;

        return $self;
    }

    public function getDefault()
    {
        return $this->default;
    }

    public function withDefault($default): self
    {
        $self = clone $this;
        $self->default = $default;

        return $self;
    }

    public function getExample()
    {
        return $this->example;
    }

    public function withExample($example): self
    {
        $self = clone $this;
        $self->example = $example;

        return $self;
    }

    public function getDeprecationReason(): ?string
    {
        return $this->deprecationReason;
    }

    public function withDeprecationReason($deprecationReason): self
    {
        $self = clone $this;
        $self->deprecationReason = $deprecationReason;

        return $self;
    }

    public function isFetchable(): ?bool
    {
        return $this->fetchable;
    }

    public function withFetchable($fetchable): self
    {
        $self = clone $this;
        $self->fetchable = $fetchable;

        return $self;
    }

    public function getFetchEager(): ?bool
    {
        return $this->fetchEager;
    }

    public function withFetchEager($fetchEager): self
    {
        $self = clone $this;
        $self->fetchEager = $fetchEager;

        return $self;
    }

    public function getJsonldContext(): ?array
    {
        return $this->jsonldContext;
    }

    public function withJsonldContext($jsonldContext): self
    {
        $self = clone $this;
        $self->jsonldContext = $jsonldContext;

        return $self;
    }

    public function getOpenapiContext(): ?array
    {
        return $this->openapiContext;
    }

    public function withOpenapiContext($openapiContext): self
    {
        $self = clone $this;
        $self->openapiContext = $openapiContext;

        return $self;
    }

    public function getPush(): ?bool
    {
        return $this->push;
    }

    public function withPush($push): self
    {
        $self = clone $this;
        $self->push = $push;

        return $self;
    }

    public function getSecurity(): ?string
    {
        return $this->security;
    }

    public function withSecurity($security): self
    {
        $self = clone $this;
        $self->security = $security;

        return $self;
    }

    public function getSecurityPostDenormalize(): ?string
    {
        return $this->securityPostDenormalize;
    }

    public function withSecurityPostDenormalize($securityPostDenormalize): self
    {
        $self = clone $this;
        $self->securityPostDenormalize = $securityPostDenormalize;

        return $self;
    }

    public function getTypes(): ?array
    {
        return $this->types;
    }

    /**
     * @param string[]|string $types
     */
    public function withTypes($types = []): self
    {
        $self = clone $this;
        $self->types = (array) $types;

        return $self;
    }

    /**
     * @return Type[]
     */
    public function getBuiltinTypes(): ?array
    {
        return $this->builtinTypes;
    }

    /**
     * @param Type[] $builtinTypes
     */
    public function withBuiltinTypes(array $builtinTypes = []): self
    {
        $self = clone $this;
        $self->builtinTypes = $builtinTypes;

        return $self;
    }

    public function getSchema(): ?array
    {
        return $this->schema;
    }

    public function withSchema(array $schema = []): self
    {
        $self = clone $this;
        $self->schema = $schema;

        return $self;
    }

    public function withInitializable(?bool $initializable): self
    {
        $self = clone $this;
        $self->initializable = $initializable;

        return $self;
    }

    public function isInitializable(): ?bool
    {
        return $this->initializable;
    }

    public function getExtraProperties(): ?array
    {
        return $this->extraProperties;
    }

    public function withExtraProperties(array $extraProperties = []): self
    {
        $self = clone $this;
        $self->extraProperties = $extraProperties;

        return $self;
    }

    /**
     * @deprecated since 2.7, to be removed in 3.0
     */
    public function withSubresource(SubresourceMetadata $subresourceMetadata): self
    {
        trigger_deprecation('api-platform/core', '2.7', 'Declaring a subresource on a property is deprecated, use alternate URLs instead.');
        $self = clone $this;
        $self->extraProperties['subresource'] = $subresourceMetadata;

        return $self;
    }

    /**
     * @deprecated since 2.7, to be removed in 3.0
     */
    public function getSubresource(): ?SubresourceMetadata
    {
        return $this->extraProperties['subresource'] ?? null;
    }

    /**
     * Represents whether the property has a subresource.
     *
     * @deprecated since 2.7, to be removed in 3.0
     */
    public function hasSubresource(): bool
    {
        return isset($this->extraProperties['subresource']);
    }

    /**
     * @deprecated since 2.6, to be removed in 3.0
     */
    public function getChildInherited(): ?string
    {
        return $this->extraProperties['childInherited'] ?? null;
    }

    /**
     * @deprecated since 2.6, to be removed in 3.0
     */
    public function hasChildInherited(): bool
    {
        return isset($this->extraProperties['childInherited']);
    }

    /**
     * @deprecated since 2.4, to be removed in 3.0
     */
    public function isChildInherited(): ?string
    {
        trigger_deprecation('api-platform/core', '2.4', sprintf('"%s::%s" is deprecated since 2.4 and will be removed in 3.0.', __CLASS__, __METHOD__));

        return $this->getChildInherited();
    }

    /**
     * @deprecated since 2.6, to be removed in 3.0
     */
    public function withChildInherited(string $childInherited): self
    {
        trigger_deprecation('api-platform/core', '2.6', sprintf('"%s::%s" is deprecated since 2.6 and will be removed in 3.0.', __CLASS__, __METHOD__));

        $metadata = clone $this;
        $metadata->extraProperties['childInherited'] = $childInherited;

        return $metadata;
    }

    /**
     * Gets IRI of this property.
     *
     * @deprecated since 2.7, to be removed in 3.0, use getTypes instead
     */
    public function getIri(): ?string
    {
        return $this->types[0] ?? null;
    }

    /**
     * Returns a new instance with the given IRI.
     *
     * @deprecated since 2.7, to be removed in 3.0, use withTypes instead
     */
    public function withIri(string $iri = null): self
    {
        trigger_deprecation('api-platform/core', '2.7', sprintf('"%s::%s" is deprecated since 2.7 and will be removed in 3.0, use Type instead.', __CLASS__, __METHOD__));

        $metadata = clone $this;
        $metadata->types = [$iri];

        return $metadata;
    }

    /**
     * Gets an attribute.
     *
     * @deprecated since 2.7, to be removed in 3.0, use getExtraProperties instead
     *
     * @param mixed|null $defaultValue
     */
    public function getAttribute(string $key, $defaultValue = null)
    {
        trigger_deprecation('api-platform/core', '2.7', sprintf('"%s::%s" is deprecated since 2.7 and will be removed in 3.0.', __CLASS__, __METHOD__));

        if (!$this->camelCaseToSnakeCaseNameConverter) {
            $this->camelCaseToSnakeCaseNameConverter = new CamelCaseToSnakeCaseNameConverter();
        }

        $propertyName = $this->camelCaseToSnakeCaseNameConverter->denormalize($key);

        if (isset($this->{$propertyName})) {
            return $this->{$propertyName} ?? $defaultValue;
        }

        return $this->extraProperties[$key] ?? $defaultValue;
    }

    /**
     * Gets attributes.
     *
     * @deprecated since 2.7, to be removed in 3.0, renamed as getExtraProperties
     */
    public function getAttributes(): ?array
    {
        return $this->extraProperties;
    }

    /**
     * Returns a new instance with the given attribute.
     *
     * @deprecated since 2.7, to be removed in 3.0, renamed as withExtraProperties
     */
    public function withAttributes(array $attributes): self
    {
        trigger_deprecation('api-platform/core', '2.7', sprintf('"%s::%s" is deprecated since 2.7 and will be removed in 3.0.', __CLASS__, __METHOD__));

        $metadata = clone $this;

        return $this->withDeprecatedAttributes($metadata, $attributes);
    }

    /**
     * Gets type.
     *
     * @deprecated since 2.7, to be removed in 3.0, renamed as getBuiltinTypes
     */
    public function getType(): ?Type
    {
        return $this->builtinTypes[0] ?? null;
    }

    /**
     * Returns a new instance with the given type.
     *
     * @deprecated since 2.7, to be removed in 3.0, renamed as withBuiltinTypes
     */
    public function withType(Type $type): self
    {
        trigger_deprecation('api-platform/core', '2.7', sprintf('"%s::%s" is deprecated since 2.7 and will be removed in 3.0, use builtinTypes instead.', __CLASS__, __METHOD__));

        $metadata = clone $this;
        $metadata->builtinTypes = [$type];

        return $metadata;
    }
}
