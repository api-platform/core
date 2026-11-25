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

namespace ApiPlatform\Tests\Functional;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\SecuredSerializedNameAccount;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class SecurityPostDenormalizeSerializedNameTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [SecuredSerializedNameAccount::class];
    }

    public function testSerializedNameCannotBypassSecurityPostDenormalize(): void
    {
        $client = self::createClient();
        $client->loginUser(new InMemoryUser('user', 'password', ['ROLE_USER']));

        $response = $client->request('PATCH', '/secured_serialized_name_accounts/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['admin_flag' => true],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertFalse($response->toArray()['admin_flag']);
    }

    public function testSerializedPathCannotBypassSecurityPostDenormalize(): void
    {
        $client = self::createClient();
        $client->loginUser(new InMemoryUser('user', 'password', ['ROLE_USER']));

        $response = $client->request('PATCH', '/secured_serialized_name_accounts/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['profile' => ['verified' => true]],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertFalse($response->toArray()['profile']['verified']);
    }
}
