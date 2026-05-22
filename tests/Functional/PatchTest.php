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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5736\Alpha;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5736\Beta;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\PatchDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\PatchDummyRelation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class PatchTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [PatchDummy::class, PatchDummyRelation::class, RelatedDummy::class, Beta::class, Alpha::class];
    }

    protected function setUp(): void
    {
        $this->recreateSchema([PatchDummy::class, PatchDummyRelation::class, RelatedDummy::class]);
    }

    public function testAcceptPatchHeader(): void
    {
        $client = self::createClient();
        $client->request('POST', '/patch_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'Hello'],
        ]);
        $response = $client->request('GET', '/patch_dummies/1', [
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        $this->assertResponseHeaderSame('Accept-Patch', 'application/merge-patch+json, application/vnd.api+json');
    }

    public function testPatchItem(): void
    {
        $client = self::createClient();
        $client->request('POST', '/patch_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'Hello'],
        ]);

        $client->request('PATCH', '/patch_dummies/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['name' => 'Patched'],
        ]);

        $this->assertJsonContains(['name' => 'Patched']);
    }

    public function testPatchRemovesPropertyWithNull(): void
    {
        $client = self::createClient();
        $client->request('POST', '/patch_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'Hello'],
        ]);

        $response = $client->request('PATCH', '/patch_dummies/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['name' => null],
        ]);

        $data = $response->toArray();
        $this->assertArrayNotHasKey('name', $data);
    }

    public function testPatchRelation(): void
    {
        $manager = $this->getManager();
        $related = new RelatedDummy();
        $manager->persist($related);
        $manager->flush();
        $dummy = new PatchDummyRelation();
        $dummy->setRelated($related);
        $manager->persist($dummy);
        $manager->flush();
        $manager->clear();

        self::createClient()->request('PATCH', '/patch_dummy_relations/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['related' => ['symfony' => 'A new name']],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $this->assertJsonEquals([
            '@context' => '/contexts/PatchDummyRelation',
            '@id' => '/patch_dummy_relations/1',
            '@type' => 'PatchDummyRelation',
            'related' => [
                '@id' => '/related_dummies/1',
                '@type' => 'https://schema.org/Product',
                'id' => 1,
                'symfony' => 'A new name',
            ],
        ]);
    }

    public function testPatchRelationWithNonIdUriVariable(): void
    {
        self::createClient()->request('PATCH', '/betas/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['alpha' => '/alphas/2'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $this->assertJsonEquals([
            '@context' => '/contexts/Beta',
            '@id' => '/betas/1',
            '@type' => 'Beta',
            'betaId' => 1,
            'alpha' => '/alphas/2',
        ]);
    }

    public function testPatchNonReadableResource(): void
    {
        // Requires use_symfony_listeners=true (Behat @use_listener @controller suite).
        // The MainController path returns the controller's return value directly, while the
        // SerializeListener kicks in only when use_symfony_listeners=true to wrap the DTO into
        // a Symfony Response. Migrate when a listener-mode test kernel is wired up.
        $this->markTestSkipped('Patch non-readable resource scenario requires use_symfony_listeners kernel mode.');
    }
}
