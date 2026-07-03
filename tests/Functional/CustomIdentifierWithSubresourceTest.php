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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SlugChildDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SlugParentDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class CustomIdentifierWithSubresourceTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [SlugParentDummy::class, SlugChildDummy::class];
    }

    protected function setUp(): void
    {
        $this->recreateSchema($this->getResources());
    }

    private function seed(): void
    {
        $client = self::createClient();
        $headers = ['Content-Type' => 'application/ld+json'];
        $client->request('POST', '/slug_parent_dummies', ['headers' => $headers, 'json' => ['slug' => 'parent-dummy']]);
        $client->request('POST', '/slug_child_dummies', [
            'headers' => $headers,
            'json' => ['slug' => 'child-dummy', 'parentDummy' => '/slug_parent_dummies/parent-dummy'],
        ]);
    }

    public function testCreateParentWithSlug(): void
    {
        self::createClient()->request('POST', '/slug_parent_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['slug' => 'parent-dummy'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonEquals([
            '@context' => '/contexts/SlugParentDummy',
            '@id' => '/slug_parent_dummies/parent-dummy',
            '@type' => 'SlugParentDummy',
            'id' => 1,
            'slug' => 'parent-dummy',
            'childDummies' => [],
        ]);
    }

    public function testCreateChildReferencingParentBySlug(): void
    {
        self::createClient()->request('POST', '/slug_parent_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['slug' => 'parent-dummy'],
        ]);
        self::createClient()->request('POST', '/slug_child_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['slug' => 'child-dummy', 'parentDummy' => '/slug_parent_dummies/parent-dummy'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonEquals([
            '@context' => '/contexts/SlugChildDummy',
            '@id' => '/slug_child_dummies/child-dummy',
            '@type' => 'SlugChildDummy',
            'id' => 1,
            'slug' => 'child-dummy',
            'parentDummy' => '/slug_parent_dummies/parent-dummy',
        ]);
    }

    public function testGetChildDummiesOfParentBySlug(): void
    {
        $this->seed();

        self::createClient()->request('GET', '/slug_parent_dummies/parent-dummy/child_dummies');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonEquals([
            '@context' => '/contexts/SlugChildDummy',
            '@id' => '/slug_parent_dummies/parent-dummy/child_dummies',
            '@type' => 'hydra:Collection',
            'hydra:member' => [
                [
                    '@id' => '/slug_child_dummies/child-dummy',
                    '@type' => 'SlugChildDummy',
                    'id' => 1,
                    'slug' => 'child-dummy',
                    'parentDummy' => '/slug_parent_dummies/parent-dummy',
                ],
            ],
            'hydra:totalItems' => 1,
        ]);
    }

    public function testGetParentOfChildBySlug(): void
    {
        $this->seed();

        self::createClient()->request('GET', '/slug_child_dummies/child-dummy/parent_dummy');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonEquals([
            '@context' => '/contexts/SlugParentDummy',
            '@id' => '/slug_child_dummies/child-dummy/parent_dummy',
            '@type' => 'SlugParentDummy',
            'id' => 1,
            'slug' => 'parent-dummy',
            'childDummies' => ['/slug_child_dummies/child-dummy'],
        ]);
    }
}
