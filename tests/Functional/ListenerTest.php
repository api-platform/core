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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7432\OriginalDataWithListeners;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ListenerTest extends ApiTestCase
{
    use SetupClassResourcesTrait;
    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            OriginalDataWithListeners::class,
        ];
    }

    public function testListener()
    {
        self::createClient()->request('PATCH', '/original_data_with_listeners/123/verify', [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => ['code' => '456'],
        ]);

        $this->assertResponseIsSuccessful();
    }
}
