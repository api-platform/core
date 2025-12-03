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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DeprecationHeader;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class DeprecationHeaderTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [DeprecationHeader::class];
    }

    public function testDeprecationHeader(): void
    {
        $response = self::createClient()->request('GET', '/deprecation_headers');

        $headers = $response->getHeaders();

        $this->assertContains('@1688169599', $headers['deprecation']);
        $this->assertContains('Sun, 30 Jun 2024 23:59:59 UTC', $headers['sunset']);
        $this->assertStringContainsString('<https://developer.example.com/deprecation>; rel="deprecation"; type="text/html"', $headers['link'][0]);
    }
}
