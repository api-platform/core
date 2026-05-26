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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CustomPut;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class CustomPutTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [CustomPut::class];
    }

    public function testPutWithoutReadOrAllowCreateReturns200(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema([CustomPut::class]);

        self::createClient()->request('PUT', '/custom_puts/1', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['foo' => 'a', 'bar' => 'b'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonEquals([
            '@context' => '/contexts/CustomPut',
            '@id' => '/custom_puts/1',
            '@type' => 'CustomPut',
            'id' => 1,
            'foo' => 'a',
            'bar' => 'b',
        ]);
    }
}
