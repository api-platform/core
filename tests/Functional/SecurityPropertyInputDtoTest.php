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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DummyDtoSecuredInput;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class SecurityPropertyInputDtoTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [DummyDtoSecuredInput::class];
    }

    public function testNonAdminCannotWriteSecuredProperty(): void
    {
        $client = self::createClient();
        $client->loginUser(new InMemoryUser('user', 'password', ['ROLE_USER']));

        $response = $client->request('PATCH', '/dummy_dto_secured_inputs/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'title' => 'updated title',
                'adminOnly' => 'hacked value',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $json = $response->toArray();
        $this->assertSame('updated title', $json['title']);
        // The adminOnly field should be silently dropped (not written) for non-admin
        $this->assertSame('existing admin value', $json['adminOnly']);
    }

    public function testAdminCanWriteSecuredProperty(): void
    {
        $client = self::createClient();
        $client->loginUser(new InMemoryUser('admin', 'password', ['ROLE_ADMIN']));

        $response = $client->request('PATCH', '/dummy_dto_secured_inputs/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'title' => 'admin updated',
                'adminOnly' => 'admin value',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $json = $response->toArray();
        $this->assertSame('admin updated', $json['title']);
        $this->assertSame('admin value', $json['adminOnly']);
    }
}
