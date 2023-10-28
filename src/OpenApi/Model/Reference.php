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

use Symfony\Component\Serializer\Annotation\SerializedName;

final class Reference
{
    use ExtensionTrait;

    public function __construct(private string $ref, private ?string $summary = null, private ?string $description = null)
    {
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function withSummary(string $summary): self
    {
        $clone = clone $this;
        $clone->summary = $summary;

        return $clone;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function withDescription(string $description): self
    {
        $clone = clone $this;
        $clone->description = $description;

        return $clone;
    }

    #[SerializedName('$ref')]
    public function getRef(): string
    {
        return $this->ref;
    }

    public function withRef(string $ref): self
    {
        $clone = clone $this;
        $clone->ref = $ref;

        return $clone;
    }
}
