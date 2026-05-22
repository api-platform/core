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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\StandardPut;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\UidIdentified;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class StandardPutTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [StandardPut::class, UidIdentified::class];
    }

    protected function setUp(): void
    {
        $this->recreateSchema([StandardPut::class, UidIdentified::class]);
    }

    public function testCreateWithPut(): void
    {
        self::createClient()->request('PUT', '/standard_puts/5', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['foo' => 'a', 'bar' => 'b'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonEquals([
            '@context' => '/contexts/StandardPut',
            '@id' => '/standard_puts/5',
            '@type' => 'StandardPut',
            'id' => 5,
            'foo' => 'a',
            'bar' => 'b',
        ]);
    }

    public function testCreateWithPutAndJsonLdAttributes(): void
    {
        self::createClient()->request('PUT', '/standard_puts/6', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                '@id' => '/standard_puts/6',
                '@context' => '/contexts/StandardPut',
                '@type' => 'StandardPut',
                'foo' => 'a',
                'bar' => 'b',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonEquals([
            '@context' => '/contexts/StandardPut',
            '@id' => '/standard_puts/6',
            '@type' => 'StandardPut',
            'id' => 6,
            'foo' => 'a',
            'bar' => 'b',
        ]);
    }

    public function testFailsWhenJsonLdIdRefersToWrongResource(): void
    {
        self::createClient()->request('PUT', '/standard_puts/7', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                '@id' => '/dummies/6',
                '@context' => '/contexts/StandardPut',
                '@type' => 'StandardPut',
                'foo' => 'a',
                'bar' => 'b',
            ],
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testFailsWhenJsonLdIdDoesNotMatchUri(): void
    {
        self::createClient()->request('PUT', '/standard_puts/7', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                '@id' => '/standard_puts/6',
                '@context' => '/contexts/StandardPut',
                '@type' => 'StandardPut',
                'foo' => 'a',
                'bar' => 'b',
            ],
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testReplaceExistingWithPut(): void
    {
        self::createClient()->request('PUT', '/standard_puts/5', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['foo' => 'a', 'bar' => 'b'],
        ]);

        self::createClient()->request('PUT', '/standard_puts/5', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['foo' => 'c'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonEquals([
            '@context' => '/contexts/StandardPut',
            '@id' => '/standard_puts/5',
            '@type' => 'StandardPut',
            'id' => 5,
            'foo' => 'c',
            'bar' => '',
        ]);
    }

    public function testCreateWithPutAndUidIdentifier(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        self::createClient()->request('PUT', '/uid_identifieds/fbcf5910-d915-4f7d-ba39-6b2957c57335', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'test'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonEquals([
            '@context' => '/contexts/UidIdentified',
            '@id' => '/uid_identifieds/fbcf5910-d915-4f7d-ba39-6b2957c57335',
            '@type' => 'UidIdentified',
            'id' => 'fbcf5910-d915-4f7d-ba39-6b2957c57335',
            'name' => 'test',
        ]);
    }

    public function testReplaceExistingUidIdentifier(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        self::createClient()->request('PUT', '/uid_identifieds/fbcf5910-d915-4f7d-ba39-6b2957c57335', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'test'],
        ]);

        self::createClient()->request('PUT', '/uid_identifieds/fbcf5910-d915-4f7d-ba39-6b2957c57335', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'bar'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonEquals([
            '@context' => '/contexts/UidIdentified',
            '@id' => '/uid_identifieds/fbcf5910-d915-4f7d-ba39-6b2957c57335',
            '@type' => 'UidIdentified',
            'id' => 'fbcf5910-d915-4f7d-ba39-6b2957c57335',
            'name' => 'bar',
        ]);
    }
}
