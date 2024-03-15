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

namespace ApiPlatform\OpenApi;

final class Options
{
    public function __construct(private readonly string $title, private readonly string $description = '', private readonly string $version = '', private readonly bool $oAuthEnabled = false, private readonly ?string $oAuthType = null, private readonly ?string $oAuthFlow = null, private readonly ?string $oAuthTokenUrl = null, private readonly ?string $oAuthAuthorizationUrl = null, private readonly ?string $oAuthRefreshUrl = null, private readonly array $oAuthScopes = [], private readonly array $apiKeys = [], private readonly ?string $contactName = null, private readonly ?string $contactUrl = null, private readonly ?string $contactEmail = null, private readonly ?string $termsOfService = null, private readonly ?string $licenseName = null, private readonly ?string $licenseUrl = null, private bool $overrideResponses = true)
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

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getOAuthEnabled(): bool
    {
        return $this->oAuthEnabled;
    }

    public function getOAuthType(): ?string
    {
        return $this->oAuthType;
    }

    public function getOAuthFlow(): ?string
    {
        return $this->oAuthFlow;
    }

    public function getOAuthTokenUrl(): ?string
    {
        return $this->oAuthTokenUrl;
    }

    public function getOAuthAuthorizationUrl(): ?string
    {
        return $this->oAuthAuthorizationUrl;
    }

    public function getOAuthRefreshUrl(): ?string
    {
        return $this->oAuthRefreshUrl;
    }

    public function getOAuthScopes(): array
    {
        return $this->oAuthScopes;
    }

    public function getApiKeys(): array
    {
        return $this->apiKeys;
    }

    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    public function getContactUrl(): ?string
    {
        return $this->contactUrl;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function getTermsOfService(): ?string
    {
        return $this->termsOfService;
    }

    public function getLicenseName(): ?string
    {
        return $this->licenseName;
    }

    public function getLicenseUrl(): ?string
    {
        return $this->licenseUrl;
    }

    public function getOverrideResponses(): bool
    {
        return $this->overrideResponses;
    }
}
