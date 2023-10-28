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

final class Info
{
    use ExtensionTrait;

    public function __construct(private string $title, private string $version, private string $description = '', private ?string $termsOfService = null, private ?Contact $contact = null, private ?License $license = null, private ?string $summary = null)
    {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getTermsOfService(): ?string
    {
        return $this->termsOfService;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function getLicense(): ?License
    {
        return $this->license;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function withTitle(string $title): self
    {
        $info = clone $this;
        $info->title = $title;

        return $info;
    }

    public function withDescription(string $description): self
    {
        $clone = clone $this;
        $clone->description = $description;

        return $clone;
    }

    public function withTermsOfService(string $termsOfService): self
    {
        $clone = clone $this;
        $clone->termsOfService = $termsOfService;

        return $clone;
    }

    public function withContact(Contact $contact): self
    {
        $clone = clone $this;
        $clone->contact = $contact;

        return $clone;
    }

    public function withLicense(License $license): self
    {
        $clone = clone $this;
        $clone->license = $license;

        return $clone;
    }

    public function withVersion(string $version): self
    {
        $clone = clone $this;
        $clone->version = $version;

        return $clone;
    }

    public function withSummary(string $summary): self
    {
        $clone = clone $this;
        $clone->summary = $summary;

        return $clone;
    }
}
