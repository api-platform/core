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

namespace ApiPlatform\Tests\Functional\Security;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\SecurityFromContainerParameter;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Component\Security\Core\User\InMemoryUser;

/**
 * End-to-end coverage for attribute parity of issue #8104: a resource declared through PHP
 * attributes uses `security: '%app.security.admin_only%'`, where the container parameter holds the
 * expression `is_granted("ROLE_ADMIN")`. The Symfony-bridge decorator must resolve the whole-string
 * %param% into that expression so the access checker evaluates it (previously it reached
 * ExpressionLanguage as the literal "%app.security.admin_only%" and threw).
 */
final class SecurityFromContainerParameterAttributeTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [SecurityFromContainerParameter::class];
    }

    public function testGrantedRoleResolvesAndAllowsAccess(): void
    {
        $client = self::createClient();
        $client->loginUser(new InMemoryUser('admin', 'password', ['ROLE_ADMIN']));

        $client->request('GET', '/security_from_container_parameter/1');
        $this->assertResponseIsSuccessful();
    }

    public function testMissingRoleIsDenied(): void
    {
        $client = self::createClient();
        $client->loginUser(new InMemoryUser('user', 'password', ['ROLE_USER']));

        $client->request('GET', '/security_from_container_parameter/1');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAnonymousIsDenied(): void
    {
        $client = self::createClient();

        $client->request('GET', '/security_from_container_parameter/1');
        $this->assertResponseStatusCodeSame(401);
    }
}
