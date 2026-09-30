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

namespace ApiPlatform\Tests\Functional\JsonLd;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\OperationShortNameWithoutApiResource\OperationShortNameResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class OperationShortNameWithoutApiResourceTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [OperationShortNameResource::class];
    }

    public function testTypeUsesOperationShortName(): void
    {
        self::createClient()->request('GET', '/operation_short_name_resources/1', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@type' => 'CustomShortName',
            '@id' => '/operation_short_name_resources/1',
        ]);
    }
}
