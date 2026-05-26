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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Headers;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCar;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class HeadersAdditionTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [DummyCar::class, Headers::class];
    }

    public function testSunsetHeaderOnResourceCollection(): void
    {
        $this->recreateSchema([DummyCar::class]);

        self::createClient()->request('GET', '/dummy_cars');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Sunset', 'Sat, 01 Jan 2050 00:00:00 +0000');
    }

    public function testDeclareHeadersFromResource(): void
    {
        self::createClient()->request('GET', '/redirect_to_foobar');

        $this->assertResponseStatusCodeSame(301);
        $this->assertResponseHeaderSame('Location', '/foobar');
        $this->assertResponseHeaderSame('Hello', 'World');
    }
}
