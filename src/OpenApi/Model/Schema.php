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

use ApiPlatform\JsonSchema\Schema as JsonSchema;

final class Schema extends \ArrayObject
{
    use ExtensionTrait;
    private readonly JsonSchema $schema;

    public function __construct(private $discriminator = null, private bool $readOnly = false, private bool $writeOnly = false, private ?string $xml = null, private $externalDocs = null, private $example = null, private bool $deprecated = false)
    {
        $this->schema = new JsonSchema();

        parent::__construct([]);
    }

    public function setDefinitions(array $definitions): void
    {
        $this->schema->setDefinitions(new \ArrayObject($definitions));
    }

    /**
     * {@inheritdoc}
     */
    public function getArrayCopy(): array
    {
        $schema = parent::getArrayCopy();
        unset($schema['schema']);

        return $schema;
    }

    public function getDefinitions(): \ArrayObject
    {
        return new \ArrayObject(array_merge($this->schema->getArrayCopy(true), $this->getArrayCopy()));
    }

    public function getDiscriminator()
    {
        return $this->discriminator;
    }

    public function getReadOnly(): bool
    {
        return $this->readOnly;
    }

    public function getWriteOnly(): bool
    {
        return $this->writeOnly;
    }

    public function getXml(): string
    {
        return $this->xml;
    }

    public function getExternalDocs()
    {
        return $this->externalDocs;
    }

    public function getExample()
    {
        return $this->example;
    }

    public function getDeprecated(): bool
    {
        return $this->deprecated;
    }

    public function withDiscriminator($discriminator): self
    {
        $clone = clone $this;
        $clone->discriminator = $discriminator;

        return $clone;
    }

    public function withReadOnly(bool $readOnly): self
    {
        $clone = clone $this;
        $clone->readOnly = $readOnly;

        return $clone;
    }

    public function withWriteOnly(bool $writeOnly): self
    {
        $clone = clone $this;
        $clone->writeOnly = $writeOnly;

        return $clone;
    }

    public function withXml(string $xml): self
    {
        $clone = clone $this;
        $clone->xml = $xml;

        return $clone;
    }

    public function withExternalDocs($externalDocs): self
    {
        $clone = clone $this;
        $clone->externalDocs = $externalDocs;

        return $clone;
    }

    public function withExample($example): self
    {
        $clone = clone $this;
        $clone->example = $example;

        return $clone;
    }

    public function withDeprecated(bool $deprecated): self
    {
        $clone = clone $this;
        $clone->deprecated = $deprecated;

        return $clone;
    }
}
