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
     * @param bool|null               $readableLink            https://api-platform.com/docs/core/serialization/#force-iri-with-relations-of-the-same-type-parentchilds-relations
     * @param bool|null               $writableLink            https://api-platform.com/docs/core/serialization/#force-iri-with-relations-of-the-same-type-parentchilds-relations
     * @param bool|null               $required                https://api-platform.com/docs/admin/validation/#client-side-validation
     * @param bool|null               $identifier              https://api-platform.com/docs/core/identifiers/
     * @param string|null             $default
     * @param mixed                   $example                 https://api-platform.com/docs/core/openapi/#using-the-openapi-and-swagger-contexts
     * @param string|null             $deprecationReason       https://api-platform.com/docs/core/deprecations/#deprecating-resource-classes-operations-and-properties
     * @param bool|null               $fetchEager              https://api-platform.com/docs/core/performance/#eager-loading
     * @param array|null              $jsonldContext           https://api-platform.com/docs/core/extending-jsonld-context/#extending-json-ld-and-hydra-contexts
     * @param array|null              $openapiContext          https://api-platform.com/docs/core/openapi/#using-the-openapi-and-swagger-contexts
     * @param bool|null               $push                    https://api-platform.com/docs/core/push-relations/
     * @param string|\Stringable|null $security                https://api-platform.com/docs/core/security
     * @param string|\Stringable|null $securityPostDenormalize https://api-platform.com/docs/core/security/#executing-access-control-rules-after-denormalization
     * @param string[]                $types                   the RDF types of this property
     * @param string[]                $iris
     * @param Type[]                  $builtinTypes
     * @param string|null             $uriTemplate             (experimental) whether to return the subRessource collection IRI instead of an iterable of IRI
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
        /**
         * The `deprecationReason` option deprecates the current operation with a deprecation message.
         *
         * <div data-code-selector>
         *
         * ```php
         * <?php
         * // api/src/Entity/Review.php
         * use ApiPlatform\Metadata\ApiProperty;
         * use ApiPlatform\Metadata\ApiResource;
         *
         * #[ApiResource]
         * class Review
         * {
         *     #[ApiProperty(deprecationReason: "Use the rating property instead")]
         *     public string $letter;
         * }
         * ```
         *
         * ```yaml
         * # api/config/api_platform/properties.yaml
         * properties:
         *     App\Entity\Review:
         *         letter:
         *             deprecationReason: 'Create a Book instead'
         * ```
         *
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/properties.xml -->
         *
         * <properties
         *         xmlns="https://api-platform.com/schema/metadata/properties-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/properties-3.0
         *         https://api-platform.com/schema/metadata/properties-3.0.xsd">
         *     <property resource="App\Entity\Review" name="letter" deprecationReason="Create a Book instead" />
         * </properties>
         * ```
         *
         * </div>
         *
         * - With JSON-lD / Hydra, [an `owl:deprecated` annotation property](https://www.w3.org/TR/owl2-syntax/#Annotation_Properties) will be added to the appropriate data structure
         * - With Swagger / OpenAPI, [a `deprecated` property](https://swagger.io/docs/specification/2-0/paths-and-operations/) will be added
         * - With GraphQL, the [`isDeprecated` and `deprecationReason` properties](https://facebook.github.io/graphql/June2018/#sec-Deprecation) will be added to the schema
         */
        private ?string $deprecationReason = null,
        private ?bool $fetchable = null,
        private ?bool $fetchEager = null,
        private ?array $jsonldContext = null,
        private ?array $openapiContext = null,
        private ?array $jsonSchemaContext = null,
        private ?bool $push = null,
        /**
         * The `security` option defines the access to the current property, on normalization process, based on Symfony Security.
         * It receives an `object` variable related to the current object, and a `property` variable related to the current property.
         *
         * <div data-code-selector>
         *
         * ```php
         * <?php
         * // api/src/Entity/Review.php
         * use ApiPlatform\Metadata\ApiProperty;
         * use ApiPlatform\Metadata\ApiResource;
         *
         * #[ApiResource]
         * class Review
         * {
         *     #[ApiProperty(security: 'is_granted("ROLE_ADMIN")')]
         *     public string $letter;
         * }
         * ```
         *
         * ```yaml
         * # api/config/api_platform/properties.yaml
         * properties:
         *     App\Entity\Review:
         *         letter:
         *             security: 'is_granted("ROLE_ADMIN")'
         * ```
         *
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/properties.xml -->
         *
         * <properties
         *         xmlns="https://api-platform.com/schema/metadata/properties-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/properties-3.0
         *         https://api-platform.com/schema/metadata/properties-3.0.xsd">
         *     <property resource="App\Entity\Review" name="letter" security="is_granted('ROLE_ADMIN')" />
         * </properties>
         * ```
         *
         * </div>
         */
        private string|\Stringable|null $security = null,
        /**
         * The `securityPostDenormalize` option defines access to the current property after the denormalization process, based on Symfony Security.
         * It receives an `object` variable related to the current object, and a `property` variable related to the current property.
         *
         * <div data-code-selector>
         *
         * ```php
         * <?php
         * // api/src/Entity/Review.php
         * use ApiPlatform\Metadata\ApiProperty;
         * use ApiPlatform\Metadata\ApiResource;
         *
         * #[ApiResource]
         * class Review
         * {
         *     #[ApiProperty(securityPostDenormalize: 'is_granted("ROLE_ADMIN")')]
         *     public string $letter;
         * }
         * ```
         *
         * ```yaml
         * # api/config/api_platform/properties.yaml
         * properties:
         *     App\Entity\Review:
         *         letter:
         *             securityPostDenormalize: 'is_granted("ROLE_ADMIN")'
         * ```
         *
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/properties.xml -->
         *
         * <properties
         *         xmlns="https://api-platform.com/schema/metadata/properties-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/properties-3.0
         *         https://api-platform.com/schema/metadata/properties-3.0.xsd">
         *     <property resource="App\Entity\Review" name="letter" securityPostDenormalize="is_granted('ROLE_ADMIN')" />
         * </properties>
         * ```
         *
         * </div>
         */
        private string|\Stringable|null $securityPostDenormalize = null,
        private array|string|null $types = null,
        /*
         * The related php types.
         */
        private ?array $builtinTypes = null,
        private ?array $schema = null,
        private ?bool $initializable = null,
        private $iris = null,
        private ?bool $genId = null,
        private ?string $uriTemplate = null,
        private array $extraProperties = [],
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
        return $this->security instanceof \Stringable ? (string) $this->security : $this->security;
    }

    public function withSecurity($security): self
    {
        $self = clone $this;
        $self->security = $security;

        return $self;
    }

    public function getSecurityPostDenormalize(): ?string
    {
        return $this->securityPostDenormalize instanceof \Stringable ? (string) $this->securityPostDenormalize : $this->securityPostDenormalize;
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

    /**
     * Whether to return the subRessource collection IRI instead of an iterable of IRI.
     *
     * @experimental
     */
    public function getUriTemplate(): ?string
    {
        return $this->uriTemplate;
    }

    public function withUriTemplate(?string $uriTemplate): self
    {
        $metadata = clone $this;
        $metadata->uriTemplate = $uriTemplate;

        return $metadata;
    }
}
