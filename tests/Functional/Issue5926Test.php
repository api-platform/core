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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5926\TestIssue5926;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @see https://github.com/api-platform/core/issues/5926
 */
final class Issue5926Test extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [TestIssue5926::class];
    }

    public static function formats(): iterable
    {
        yield ['application/json', 'application/json; charset=utf-8'];
        yield ['application/vnd.api+json', 'application/vnd.api+json; charset=utf-8'];
        yield ['application/ld+json', 'application/ld+json; charset=utf-8'];
        yield ['application/hal+json', 'application/hal+json; charset=utf-8'];
    }

    #[DataProvider('formats')]
    public function testGetWriteResourceWithEmbeddedNonResourceCollection(string $accept, string $expectedContentType): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        self::createClient()->request('GET', '/test_issue5926s/1', [
            'headers' => ['Accept' => $accept],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', $expectedContentType);
    }

    private function isMongoDB(): bool
    {
        return 'mongodb' === static::getContainer()->getParameter('kernel.environment');
    }
}
