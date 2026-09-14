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

namespace ApiPlatform\Tests\Functional\Doctrine;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Foo as ExistingFoo;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\JoinedInheritanceSerializerGroups\BarJoined;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\JoinedInheritanceSerializerGroups\BarJoinedA;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\JoinedInheritanceSerializerGroups\BarJoinedB;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\JoinedInheritanceSerializerGroups\SerializerGroupsOwner;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class JoinedInheritanceSerializerGroupsTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [ExistingFoo::class, SerializerGroupsOwner::class, BarJoined::class, BarJoinedA::class, BarJoinedB::class];
    }

    public function testJoinedInheritanceSubclassGroupsEmbedRelation(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Not tested with mongodb.');
        }

        $this->recreateSchema([SerializerGroupsOwner::class, BarJoined::class, BarJoinedA::class, BarJoinedB::class]);

        $manager = $this->getManager();
        $barJoinedA = new BarJoinedA();
        $barJoinedA->setY('y_value');
        $manager->persist($barJoinedA);

        $serializerGroupsOwner = new SerializerGroupsOwner();
        $serializerGroupsOwner->setBarJoined($barJoinedA);
        $manager->persist($serializerGroupsOwner);
        $manager->flush();

        $response = self::createClient()->request('GET', '/joined-inheritance-serializer-groups/1', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => '/joined-inheritance-serializer-groups/1',
            'barJoined' => [
                '@type' => 'JoinedInheritanceSerializerGroupsBarA',
                'y' => 'y_value',
            ],
        ]);
    }
}
