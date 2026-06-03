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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CustomWritableIdentifierDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class CustomWritableIdentifierTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [CustomWritableIdentifierDummy::class];
    }

    protected function setUp(): void
    {
        $this->recreateSchema([CustomWritableIdentifierDummy::class]);
    }

    private function createWithSlug(string $name, string $slug): void
    {
        self::createClient()->request('POST', '/custom_writable_identifier_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => $name, 'slug' => $slug],
        ]);
    }

    public function testCreateWithWritableSlug(): void
    {
        $this->createWithSlug('My Dummy', 'my_slug');

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertResponseHeaderSame('Content-Location', '/custom_writable_identifier_dummies/my_slug.jsonld');
        $this->assertResponseHeaderSame('Location', '/custom_writable_identifier_dummies/my_slug');
        $this->assertJsonEquals([
            '@context' => '/contexts/CustomWritableIdentifierDummy',
            '@id' => '/custom_writable_identifier_dummies/my_slug',
            '@type' => 'CustomWritableIdentifierDummy',
            'slug' => 'my_slug',
            'name' => 'My Dummy',
        ]);
    }

    public function testGetItemBySlug(): void
    {
        $this->createWithSlug('My Dummy', 'my_slug');

        self::createClient()->request('GET', '/custom_writable_identifier_dummies/my_slug');

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonEquals([
            '@context' => '/contexts/CustomWritableIdentifierDummy',
            '@id' => '/custom_writable_identifier_dummies/my_slug',
            '@type' => 'CustomWritableIdentifierDummy',
            'slug' => 'my_slug',
            'name' => 'My Dummy',
        ]);
    }

    public function testGetCollection(): void
    {
        $this->createWithSlug('My Dummy', 'my_slug');

        self::createClient()->request('GET', '/custom_writable_identifier_dummies');

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonEquals([
            '@context' => '/contexts/CustomWritableIdentifierDummy',
            '@id' => '/custom_writable_identifier_dummies',
            '@type' => 'hydra:Collection',
            'hydra:member' => [[
                '@id' => '/custom_writable_identifier_dummies/my_slug',
                '@type' => 'CustomWritableIdentifierDummy',
                'slug' => 'my_slug',
                'name' => 'My Dummy',
            ]],
            'hydra:totalItems' => 1,
        ]);
    }

    public function testPutChangesIdentifier(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->createWithSlug('My Dummy', 'my_slug');

        self::createClient()->request('PUT', '/custom_writable_identifier_dummies/my_slug', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'My Dummy modified', 'slug' => 'slug_modified'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Location', '/custom_writable_identifier_dummies/slug_modified.jsonld');
        $this->assertJsonEquals([
            '@context' => '/contexts/CustomWritableIdentifierDummy',
            '@id' => '/custom_writable_identifier_dummies/slug_modified',
            '@type' => 'CustomWritableIdentifierDummy',
            'slug' => 'slug_modified',
            'name' => 'My Dummy modified',
        ]);
    }

    public function testApiDocReportsClass(): void
    {
        $response = self::createClient()->request('GET', '/docs.jsonld');

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();
        $classes = array_filter($data['hydra:supportedClass'], static fn ($c) => 'CustomWritableIdentifierDummy' === $c['hydra:title']);
        $this->assertCount(1, $classes);
        $class = reset($classes);
        $properties = array_column($class['hydra:supportedProperty'] ?? [], 'hydra:title');
        $this->assertContains('name', $properties);
        $this->assertContains('slug', $properties);
    }

    public function testDelete(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->createWithSlug('My Dummy', 'my_slug');

        self::createClient()->request('DELETE', '/custom_writable_identifier_dummies/my_slug');

        $this->assertResponseStatusCodeSame(204);
    }
}
