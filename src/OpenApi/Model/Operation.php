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

namespace ApiPlatform\OpenApi\Model;

final class Operation
{
    use ExtensionTrait;

    public function __construct(private ?string $operationId = null, private ?array $tags = null, private ?array $responses = null, private ?string $summary = null, private ?string $description = null, private ?ExternalDocumentation $externalDocs = null, private ?array $parameters = null, private ?RequestBody $requestBody = null, private ?\ArrayObject $callbacks = null, private ?bool $deprecated = null, private ?array $security = null, private ?array $servers = null, array $extensionProperties = [])
    {
        $this->extensionProperties = $extensionProperties;
    }

    /**
     * @param string $status
     */
    public function addResponse(Response $response, $status = 'default'): self
    {
        $this->responses[$status] = $response;

        return $this;
    }

    public function getOperationId(): ?string
    {
        return $this->operationId;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function getResponses(): ?array
    {
        return $this->responses;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getExternalDocs(): ?ExternalDocumentation
    {
        return $this->externalDocs;
    }

    public function getParameters(): ?array
    {
        return $this->parameters;
    }

    public function getRequestBody(): ?RequestBody
    {
        return $this->requestBody;
    }

    public function getCallbacks(): ?\ArrayObject
    {
        return $this->callbacks;
    }

    public function getDeprecated(): ?bool
    {
        return $this->deprecated;
    }

    public function getSecurity(): ?array
    {
        return $this->security;
    }

    public function getServers(): ?array
    {
        return $this->servers;
    }

    public function withOperationId(string $operationId): self
    {
        $clone = clone $this;
        $clone->operationId = $operationId;

        return $clone;
    }

    public function withTags(array $tags): self
    {
        $clone = clone $this;
        $clone->tags = $tags;

        return $clone;
    }

    public function withResponses(array $responses): self
    {
        $clone = clone $this;
        $clone->responses = $responses;

        return $clone;
    }

    public function withResponse(int|string $status, Response $response): self
    {
        $clone = clone $this;
        if (!\is_array($clone->responses)) {
            $clone->responses = [];
        }
        $clone->responses[(string) $status] = $response;

        return $clone;
    }

    public function withSummary(string $summary): self
    {
        $clone = clone $this;
        $clone->summary = $summary;

        return $clone;
    }

    public function withDescription(string $description): self
    {
        $clone = clone $this;
        $clone->description = $description;

        return $clone;
    }

    public function withExternalDocs(ExternalDocumentation $externalDocs): self
    {
        $clone = clone $this;
        $clone->externalDocs = $externalDocs;

        return $clone;
    }

    public function withParameters(array $parameters): self
    {
        $clone = clone $this;
        $clone->parameters = $parameters;

        return $clone;
    }

    public function withParameter(Parameter $parameter): self
    {
        $clone = clone $this;
        if (!\is_array($clone->parameters)) {
            $clone->parameters = [];
        }
        $clone->parameters[] = $parameter;

        return $clone;
    }

    public function withRequestBody(?RequestBody $requestBody = null): self
    {
        $clone = clone $this;
        $clone->requestBody = $requestBody;

        return $clone;
    }

    public function withCallbacks(\ArrayObject $callbacks): self
    {
        $clone = clone $this;
        $clone->callbacks = $callbacks;

        return $clone;
    }

    public function withDeprecated(bool $deprecated): self
    {
        $clone = clone $this;
        $clone->deprecated = $deprecated;

        return $clone;
    }

    public function withSecurity(?array $security = null): self
    {
        $clone = clone $this;
        $clone->security = $security;

        return $clone;
    }

    public function withServers(?array $servers = null): self
    {
        $clone = clone $this;
        $clone->servers = $servers;

        return $clone;
    }
}
