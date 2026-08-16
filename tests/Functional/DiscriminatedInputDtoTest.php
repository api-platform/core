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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedInputDto\ChannelResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class DiscriminatedInputDtoTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [ChannelResource::class];
    }

    public function testObjectTypedSubclassOnlyConstructorArgumentIsDenormalized(): void
    {
        self::createClient()->request('POST', '/discriminated_notification_channels', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'type' => 'webhook',
                'config' => ['url' => 'https://example.com/hook', 'retries' => 5],
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'type' => 'webhook',
            'url' => 'https://example.com/hook',
            'retries' => 5,
        ]);
    }

    public function testObjectTypedSubclassOnlyPropertyIsDenormalizedOnPatch(): void
    {
        self::createClient()->request('PATCH', '/discriminated_notification_channels/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'type' => 'webhook_patch',
                'config' => ['url' => 'https://example.com/hook', 'retries' => 5],
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'type' => 'webhook_patch',
            'url' => 'https://example.com/hook',
            'retries' => 5,
        ]);
    }
}
