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

namespace ApiPlatform\Core\OpenApi;

final class Options
{
    private $title;
    private $description;
    private $version;
    private $oAuthEnabled;
    private $oAuthType;
    private $oAuthFlow;
    private $oAuthTokenUrl;
    private $oAuthAuthorizationUrl;
    private $oAuthRefreshUrl;
    private $oAuthScopes;
    private $apiKeys;

    public function __construct(string $title, string $description = '', string $version = '', bool $oAuthEnabled = false, string $oAuthType = '', string $oAuthFlow = '', string $oAuthTokenUrl = '', string $oAuthAuthorizationUrl = '', string $oAuthRefreshUrl = '', array $oAuthScopes = [], array $apiKeys = [])
    {
        $this->title = $title;
        $this->description = $description;
        $this->version = $version;
        $this->oAuthEnabled = $oAuthEnabled;
        $this->oAuthType = $oAuthType;
        $this->oAuthFlow = $oAuthFlow;
        $this->oAuthTokenUrl = $oAuthTokenUrl;
        $this->oAuthAuthorizationUrl = $oAuthAuthorizationUrl;
        $this->oAuthRefreshUrl = $oAuthRefreshUrl;
        $this->oAuthScopes = $oAuthScopes;
        $this->apiKeys = $apiKeys;
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

    public function getOAuthType(): string
    {
        return $this->oAuthType;
    }

    public function getOAuthFlow(): string
    {
        return $this->oAuthFlow;
    }

    public function getOAuthTokenUrl(): string
    {
        return $this->oAuthTokenUrl;
    }

    public function getOAuthAuthorizationUrl(): string
    {
        return $this->oAuthAuthorizationUrl;
    }

    public function getOAuthRefreshUrl(): string
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
}
