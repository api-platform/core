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

use Symfony\Component\PropertyInfo\Type;

/**
 * ApiProperty annotation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::TARGET_PARAMETER)]
final class ApiProperty
{
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
    private $jsonSchemaContext;
    private $push;
    private $security;
    private $securityPostDenormalize;

    /**
     * @var string|string[]|null
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
    private $genId;

    /**
     * @var string[]
     */
    private $iris;

    /**
     * @var array
     */
    private $extraProperties;

    /**
     * @param bool|null            $readableLink            https://api-platform.com/docs/core/serialization/#force-iri-with-relations-of-the-same-type-parentchilds-relations
     * @param bool|null            $writableLink            https://api-platform.com/docs/core/serialization/#force-iri-with-relations-of-the-same-type-parentchilds-relations
     * @param bool|null            $required                https://api-platform.com/docs/admin/validation/#client-side-validation
     * @param bool|null            $identifier              https://api-platform.com/docs/core/identifiers/
     * @param string|null          $default
     * @param string|null          $example                 https://api-platform.com/docs/core/openapi/#using-the-openapi-and-swagger-contexts
     * @param string|null          $deprecationReason       https://api-platform.com/docs/core/deprecations/#deprecating-resource-classes-operations-and-properties
     * @param bool|null            $fetchEager              https://api-platform.com/docs/core/performance/#eager-loading
     * @param array|null           $jsonldContext           https://api-platform.com/docs/core/extending-jsonld-context/#extending-json-ld-and-hydra-contexts
     * @param array|null           $openapiContext          https://api-platform.com/docs/core/openapi/#using-the-openapi-and-swagger-contexts
     * @param bool|null            $push                    https://api-platform.com/docs/core/push-relations/
     * @param string|null          $security                https://api-platform.com/docs/core/security
     * @param string|null          $securityPostDenormalize https://api-platform.com/docs/core/security/#executing-access-control-rules-after-denormalization
     * @param array|null           $types                   the RDF types of this property
     * @param string|string[]|null $iris
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
        ?array $jsonSchemaContext = null,
        ?bool $push = null,
        ?string $security = null,
        ?string $securityPostDenormalize = null,

        $types = null,
        ?array $builtinTypes = null,
        ?array $schema = null,
        ?bool $initializable = null,
        ?bool $genId = null,

        $iris = null,

        // attributes
        array $extraProperties = []
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
        $this->jsonSchemaContext = $jsonSchemaContext;
        $this->push = $push;
        $this->security = $security;
        $this->openapiContext = $openapiContext;
        $this->securityPostDenormalize = $securityPostDenormalize;
        $this->types = null === $types ? null : (array) $types;
        $this->builtinTypes = $builtinTypes;
        $this->schema = $schema;
        $this->initializable = $initializable;
        $this->genId = $genId;
        $this->iris = $iris;
        $this->extraProperties = $extraProperties;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function withDescription(string $description): self
    {
        $self = clone $this;
        $self->description = $description;

        return $self;
    }

    public function isReadable(): ?bool
    {
        return $this->readable;
    }

    public function withReadable(bool $readable): self
    {
        $self = clone $this;
        $self->readable = $readable;

        return $self;
    }

    public function isWritable(): ?bool
    {
        return $this->writable;
    }

    public function withWritable(bool $writable): self
    {
        $self = clone $this;
        $self->writable = $writable;

        return $self;
    }

    public function isReadableLink(): ?bool
    {
        return $this->readableLink;
    }

    public function withReadableLink(bool $readableLink): self
    {
        $self = clone $this;
        $self->readableLink = $readableLink;

        return $self;
    }

    public function isWritableLink(): ?bool
    {
        return $this->writableLink;
    }

    public function withWritableLink(bool $writableLink): self
    {
        $self = clone $this;
        $self->writableLink = $writableLink;

        return $self;
    }

    public function isRequired(): ?bool
    {
        return $this->required;
    }

    public function withRequired(bool $required): self
    {
        $self = clone $this;
        $self->required = $required;

        return $self;
    }

    public function isIdentifier(): ?bool
    {
        return $this->identifier;
    }

    public function withIdentifier(bool $identifier): self
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

    public function getJsonSchemaContext(): ?array
    {
        return $this->jsonSchemaContext;
    }

    public function withJsonSchemaContext($jsonSchemaContext): self
    {
        $self = clone $this;
        $self->jsonSchemaContext = $jsonSchemaContext;

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
     * Gets IRI of this property.
     */
    public function getIris()
    {
        return $this->iris;
    }

    /**
     * Returns a new instance with the given IRI.
     *
     * @param string|string[] $iris
     */
    public function withIris($iris): self
    {
        $metadata = clone $this;
        $metadata->iris = (array) $iris;

        return $metadata;
    }

    /**
     * Whether to generate a skolem iri on anonymous resources.
     */
    public function getGenId()
    {
        return $this->genId;
    }

    public function withGenId(bool $genId): self
    {
        $metadata = clone $this;
        $metadata->genId = $genId;

        return $metadata;
    }
}
