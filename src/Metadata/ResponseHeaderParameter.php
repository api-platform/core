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

use ApiPlatform\OpenApi\Model\Header;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use ApiPlatform\State\ParameterProviderInterface;

/**
 * Declares an HTTP response header for an operation. Used for OpenAPI documentation
 * and to set a response header at runtime.
 *
 * - A static value can be provided through the `$default` argument.
 * - A `$provider` (a callable or a service ID resolving to a
 *   {@see ParameterProviderInterface}) can compute the value at runtime by calling
 *   {@see Parameter::setValue()}.
 * - A header that resolves to no value is not emitted. Use an empty string to produce
 *   an empty header value.
 * - `$openApiHeader` overrides the generated OpenAPI documentation entirely. It is named
 *   apart from the inherited `$openApi` because the latter is a promoted property of
 *   {@see Parameter} and cannot be re-typed to carry a {@see Header}.
 */
final class ResponseHeaderParameter extends Parameter
{
    /**
     * @param array<string, mixed>|null                       $schema
     * @param ParameterProviderInterface|callable|string|null $provider
     * @param array<string, mixed>                            $extraProperties
     */
    public function __construct(
        ?string $key = null,
        ?array $schema = null,
        ?string $description = null,
        mixed $provider = null,
        mixed $default = null,
        ?bool $required = null,
        private ?bool $deprecated = null,
        OpenApiParameter|array|false|null $openApi = null,
        private ?Header $openApiHeader = null,
        array $extraProperties = [],
    ) {
        parent::__construct(
            key: $key,
            schema: $schema,
            openApi: $openApi,
            provider: $provider,
            description: $description,
            required: $required,
            extraProperties: $extraProperties,
            default: $default,
        );
    }

    public function getOpenApiHeader(): ?Header
    {
        return $this->openApiHeader;
    }

    public function withOpenApiHeader(Header $openApiHeader): static
    {
        $self = clone $this;
        $self->openApiHeader = $openApiHeader;

        return $self;
    }

    public function getDeprecated(): ?bool
    {
        return $this->deprecated;
    }

    public function withDeprecated(bool $deprecated): static
    {
        $self = clone $this;
        $self->deprecated = $deprecated;

        return $self;
    }
}
