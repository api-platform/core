<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\tests\OAuth2\Config;

use ApiPlatform\Core\OAuth2\Config\OAuth2Config;

/**
 * @author Daniel Kiesel <icodr8@gmail.com>
 */
class OAuth2ConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testValueObject()
    {
        $config = new OAuth2Config(
            true,
            'clientid123',
            'clientSecret123',
            'oauth2',
            'application',
            '/oauth/v2/token',
            '/oauth/v2/auth',
            [
                'scope param',
            ]
        );
        $this->assertTrue($config->isEnabled());
        $this->assertEquals('clientid123', $config->getClientId());
        $this->assertEquals('clientSecret123', $config->getClientSecret());
        $this->assertEquals('oauth2', $config->getType());
        $this->assertEquals('application', $config->getFlow());
        $this->assertEquals('/oauth/v2/token', $config->getTokenUrl());
        $this->assertEquals('/oauth/v2/auth', $config->getAuthorizationUrl());
        $this->assertEquals(['scope param'], $config->getScopes());
    }

    public function testSerializeObject()
    {
        $config = new OAuth2Config(
            false,
            'clientid123',
            'clientSecret123',
            'oauth2',
            'application',
            '/oauth/v2/token',
            '/oauth/v2/auth',
            [
                'scope param',
            ]
        );
        $this->assertEquals([
            'enabled' => false,
            'clientId' => 'clientid123',
            'clientSecret' => 'clientSecret123',
            'type' => 'oauth2',
            'flow' => 'application',
            'tokenUrl' => '/oauth/v2/token',
            'authorizationUrl' => '/oauth/v2/auth',
            'scopes' => ['scope param'],
        ], $config->serialize());
    }
}
