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

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\IsGrantedTestResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class IsGrantedTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [IsGrantedTestResource::class];
    }

    public function testGetIsGrantedAsAdmin(): void
    {
        $client = self::createClient();
        $client->loginUser(new InMemoryUser('admin', 'password', ['ROLE_ADMIN']));

        $client->request('GET', '/is_granted_tests/1');
        $this->assertResponseIsSuccessful();
    }

    public function testGetIsGrantedAsUser(): void
    {
        $client = self::createClient();
        $client->loginUser(new InMemoryUser('user', 'password', ['ROLE_USER']));

        $client->request('GET', '/is_granted_tests/1');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testGetIsGrantedAsAnonymous(): void
    {
        $client = self::createClient();

        $client->request('GET', '/is_granted_tests/1');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetIsGrantedShouldNotCallProvider(): void
    {
        $client = self::createClient();

        $client->request('GET', '/is_granted_test_call_provider/1');
        $this->assertResponseStatusCodeSame(401);
    }
}
