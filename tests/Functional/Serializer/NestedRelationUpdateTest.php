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

namespace ApiPlatform\Tests\Functional\Serializer;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\NestedRelationPatch\NestedDataPool;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\NestedRelationPatch\NestedDataPoolStartup;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\NestedRelationPatch\NestedStartup;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class NestedRelationUpdateTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [NestedDataPool::class, NestedDataPoolStartup::class, NestedStartup::class];
    }

    public function testPatchReplacesAlreadyPopulatedNestedRelationFromEmbeddedIri(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema(self::getResources());

        $manager = static::getContainer()->get('doctrine')->getManager();

        $startupOne = new NestedStartup();
        $startupOne->setName('one');
        $manager->persist($startupOne);

        $startupTwo = new NestedStartup();
        $startupTwo->setName('two');
        $manager->persist($startupTwo);

        $dataPoolStartup = new NestedDataPoolStartup();
        $dataPoolStartup->setStartup($startupOne);
        $manager->persist($dataPoolStartup);

        $dataPool = new NestedDataPool();
        $dataPool->setDataPoolStartup($dataPoolStartup);
        $manager->persist($dataPool);

        $manager->flush();

        $dataPoolId = $dataPool->getId();
        $startupTwoIri = '/nested_startups/'.$startupTwo->getId();
        $manager->clear();

        self::createClient()->request('PATCH', '/nested_data_pools/'.$dataPoolId, [
            'json' => [
                'dataPoolStartup' => [
                    'startup' => [
                        '@id' => $startupTwoIri,
                        'id' => $startupTwo->getId(),
                    ],
                ],
            ],
            'headers' => [
                'content-type' => 'application/ld+json',
            ],
        ]);

        static::assertResponseIsSuccessful();

        $manager->clear();
        $updated = $manager->getRepository(NestedDataPool::class)->find($dataPoolId);
        static::assertSame($startupTwo->getId(), $updated->getDataPoolStartup()->getStartup()->getId());
    }

    public function testPatchKeepsAndMutatesNestedRelationWhenEmbeddedIriMatches(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema(self::getResources());

        $manager = static::getContainer()->get('doctrine')->getManager();

        $startup = new NestedStartup();
        $startup->setName('one');
        $manager->persist($startup);

        $dataPoolStartup = new NestedDataPoolStartup();
        $dataPoolStartup->setStartup($startup);
        $manager->persist($dataPoolStartup);

        $dataPool = new NestedDataPool();
        $dataPool->setDataPoolStartup($dataPoolStartup);
        $manager->persist($dataPool);

        $manager->flush();

        $dataPoolId = $dataPool->getId();
        $startupId = $startup->getId();
        $startupIri = '/nested_startups/'.$startupId;
        $manager->clear();

        self::createClient()->request('PATCH', '/nested_data_pools/'.$dataPoolId, [
            'json' => [
                'dataPoolStartup' => [
                    'startup' => [
                        '@id' => $startupIri,
                        'name' => 'renamed',
                    ],
                ],
            ],
            'headers' => [
                'content-type' => 'application/ld+json',
            ],
        ]);

        static::assertResponseIsSuccessful();

        $manager->clear();
        $updated = $manager->getRepository(NestedDataPool::class)->find($dataPoolId);
        static::assertSame($startupId, $updated->getDataPoolStartup()->getStartup()->getId());
        static::assertSame('renamed', $updated->getDataPoolStartup()->getStartup()->getName());
    }

    public function testPatchWithDanglingNestedIriFailsAndKeepsRelationUntouched(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema(self::getResources());

        $manager = static::getContainer()->get('doctrine')->getManager();

        $startup = new NestedStartup();
        $startup->setName('one');
        $manager->persist($startup);

        $dataPoolStartup = new NestedDataPoolStartup();
        $dataPoolStartup->setStartup($startup);
        $manager->persist($dataPoolStartup);

        $dataPool = new NestedDataPool();
        $dataPool->setDataPoolStartup($dataPoolStartup);
        $manager->persist($dataPool);

        $manager->flush();

        $dataPoolId = $dataPool->getId();
        $startupId = $startup->getId();
        $manager->clear();

        self::createClient()->request('PATCH', '/nested_data_pools/'.$dataPoolId, [
            'json' => [
                'dataPoolStartup' => [
                    'startup' => [
                        '@id' => '/nested_startups/99999',
                        'name' => 'should not be applied',
                    ],
                ],
            ],
            'headers' => [
                'content-type' => 'application/ld+json',
            ],
        ]);

        static::assertResponseStatusCodeSame(400);

        $manager->clear();
        $untouched = $manager->getRepository(NestedDataPool::class)->find($dataPoolId);
        static::assertSame($startupId, $untouched->getDataPoolStartup()->getStartup()->getId());
        static::assertSame('one', $untouched->getDataPoolStartup()->getStartup()->getName());
    }
}
