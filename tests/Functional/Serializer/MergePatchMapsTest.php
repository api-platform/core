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

namespace ApiPlatform\Tests\Functional\Serializer;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Settings;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class MergePatchMapsTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [Settings::class];
    }

    public function testMergePatchMergesMapWhenOptedIn(): void
    {
        $response = self::createClient()->request('PATCH', '/merge_patch_settings/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['settings' => ['b' => null, 'c' => ['d' => 1]]],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['a' => 1, 'c' => ['e' => 2, 'd' => 1]], $response->toArray()['settings']);
    }

    public function testMergePatchValidatesMergedMap(): void
    {
        self::createClient()->request('PATCH', '/merge_patch_settings/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['settings' => ['x' => 1]],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['violations' => [['propertyPath' => 'settings']]]);
    }

    public function testMergePatchReplacesMapWithoutOptIn(): void
    {
        $response = self::createClient()->request('PATCH', '/merge_patch_settings/1/replace', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['settings' => ['b' => null, 'c' => ['d' => 1]]],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['b' => null, 'c' => ['d' => 1]], $response->toArray()['settings']);
    }
}
