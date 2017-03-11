<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\OAuth2\Config;

/**
 * OAuth2 config.
 *
 * @author Daniel Kiesel <icodr8@gmail.com>
 */
final class OAuth2Config implements \Serializable
{
    private $enabled;
    private $clientId;
    private $clientSecret;
    private $type;
    private $flow;
    private $tokenUrl;
    private $authorizationUrl;
    private $scopes;

    public function __construct(
        bool $enabled = false,
        string $clientId,
        string $clientSecret,
        string $type,
        string $flow,
        string $tokenUrl,
        string $authorizationUrl,
        array $scopes
    ) {
        $this->enabled = $enabled;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->type = $type;
        $this->flow = $flow;
        $this->tokenUrl = $tokenUrl;
        $this->authorizationUrl = $authorizationUrl;
        $this->scopes = $scopes;
    }

    public function serialize()
    {
        return [
            'enabled' => $this->enabled,
            'clientId' => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'type' => $this->type,
            'flow' => $this->flow,
            'tokenUrl' => $this->tokenUrl,
            'authorizationUrl' => $this->authorizationUrl,
            'scopes' => $this->scopes
        ];
    }

    public function unserialize($serialized)
    {
        $this->enabled = $serialized['enabled'];
        $this->clientId = $serialized['clientId'];
        $this->clientSecret = $serialized['clientSecret'];
        $this->type = $serialized['type'];
        $this->flow = $serialized['flow'];
        $this->tokenUrl = $serialized['tokenUrl'];
        $this->authorizationUrl = $serialized['authorizationUrl'];
        $this->scopes = $serialized['scopes'];
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getFlow()
    {
        return $this->flow;
    }

    /**
     * @return string
     */
    public function getTokenUrl()
    {
        return $this->tokenUrl;
    }

    /**
     * @return string
     */
    public function getAuthorizationUrl()
    {
        return $this->authorizationUrl;
    }

    /**
     * @return array
     */
    public function getScopes()
    {
        return $this->scopes;
    }
}
