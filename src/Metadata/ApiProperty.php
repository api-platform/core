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
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::TARGET_PARAMETER | \Attribute::TARGET_CLASS_CONSTANT)]
final class ApiProperty
{
    /**
     * @param bool|null   $readableLink            https://api-platform.com/docs/core/serialization/#force-iri-with-relations-of-the-same-type-parentchilds-relations
     * @param bool|null   $writableLink            https://api-platform.com/docs/core/serialization/#force-iri-with-relations-of-the-same-type-parentchilds-relations
     * @param bool|null   $required                https://api-platform.com/docs/admin/validation/#client-side-validation
     * @param bool|null   $identifier              https://api-platform.com/docs/core/identifiers/
     * @param string|null $default
     * @param mixed       $example                 https://api-platform.com/docs/core/openapi/#using-the-openapi-and-swagger-contexts
     * @param string|null $deprecationReason       https://api-platform.com/docs/core/deprecations/#deprecating-resource-classes-operations-and-properties
     * @param bool|null   $fetchEager              https://api-platform.com/docs/core/performance/#eager-loading
     * @param array|null  $jsonldContext           https://api-platform.com/docs/core/extending-jsonld-context/#extending-json-ld-and-hydra-contexts
     * @param array|null  $openapiContext          https://api-platform.com/docs/core/openapi/#using-the-openapi-and-swagger-contexts
     * @param bool|null   $push                    https://api-platform.com/docs/core/push-relations/
     * @param string|null $security                https://api-platform.com/docs/core/security
     * @param string|null $securityPostDenormalize https://api-platform.com/docs/core/security/#executing-access-control-rules-after-denormalization
     * @param string[]    $types                   the RDF types of this property
     * @param string[]    $iris
     * @param Type[]      $builtinTypes
     */
    public function __construct(
        private ?string $description = null,
        private ?bool $readable = null,
        private ?bool $writable = null,
        private ?bool $readableLink = null,
        private ?bool $writableLink = null,
        private ?bool $required = null,
        private ?bool $identifier = null,
        private $default = null,
        private mixed $example = null,
        private ?string $deprecationReason = null,
        private ?bool $fetchable = null,
        private ?bool $fetchEager = null,
        private ?array $jsonldContext = null,
        private ?array $openapiContext = null,
        private ?array $jsonSchemaContext = null,
        private ?bool $push = null,
        private ?string $security = null,
        private ?string $securityPostDenormalize = null,
        private array|string|null $types = null,
        /**
         * The related php types.
         */
        private ?array $builtinTypes = null,
        private ?array $schema = null,
        private ?bool $initializable = null,
        private $iris = null,
        private ?bool $genId = null,
        private array $extraProperties = []
    ) {
        if (\is_string($types)) {
            $this->types = (array) $types;
        }
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

    public function getExample(): mixed
    {
        return $this->example;
    }

    public function withExample(mixed $example): self
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
    public function withTypes(array|string $types = []): self
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
    public function withIris(string|array $iris): self
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
