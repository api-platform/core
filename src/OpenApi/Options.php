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

    public function getTitle()
    {
        return $this->title;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getOAuthEnabled()
    {
        return $this->oAuthEnabled;
    }

    public function getOAuthType()
    {
        return $this->oAuthType;
    }

    public function getOAuthFlow()
    {
        return $this->oAuthFlow;
    }

    public function getOAuthTokenUrl()
    {
        return $this->oAuthTokenUrl;
    }

    public function getOAuthAuthorizationUrl()
    {
        return $this->oAuthAuthorizationUrl;
    }

    public function getOAuthRefreshUrl()
    {
        return $this->oAuthRefreshUrl;
    }

    public function getOAuthScopes()
    {
        return $this->oAuthScopes;
    }

    public function getApiKeys()
    {
        return $this->apiKeys;
    }
}
