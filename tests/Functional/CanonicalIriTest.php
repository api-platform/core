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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CanonicalIriEntity;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class CanonicalIriTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [CanonicalIriEntity::class];
    }

    public function testItemOperationWithCustomUriTemplateUsesTheResourceItemUriTemplate(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema([CanonicalIriEntity::class]);
        $this->createEntity();

        self::createClient()->request('PATCH', '/canonical_iri_entities/1/rename', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['name' => 'renamed'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            '@id' => '/canonical_iri_entities/1',
            'name' => 'renamed',
        ]);
    }

    public function testOperationItemUriTemplateHasPrecedenceOverTheResourceOne(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema([CanonicalIriEntity::class]);
        $this->createEntity();

        self::createClient()->request('PATCH', '/canonical_iri_entities/1/override', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['name' => 'renamed again'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            '@id' => '/canonical_iri_entities/1/rename',
            'name' => 'renamed again',
        ]);
    }

    private function createEntity(): void
    {
        $manager = $this->getManager();
        $entity = new CanonicalIriEntity();
        $entity->id = 1;
        $entity->name = 'initial';
        $manager->persist($entity);
        $manager->flush();
    }
}
