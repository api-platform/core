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

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8113\BarJoined;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8113\BarJoinedA;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8113\BarJoinedB;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8113\Foo;
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
        return [Foo::class, BarJoined::class, BarJoinedA::class, BarJoinedB::class];
    }

    public function testJoinedInheritanceSubclassGroupsEmbedRelation(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Not tested with mongodb.');
        }

        $this->recreateSchema([Foo::class, BarJoined::class, BarJoinedA::class, BarJoinedB::class]);

        $manager = $this->getManager();
        $barJoinedA = new BarJoinedA();
        $barJoinedA->setY('y_value');
        $manager->persist($barJoinedA);

        $foo = new Foo();
        $foo->setBarJoined($barJoinedA);
        $manager->persist($foo);
        $manager->flush();

        $response = self::createClient()->request('GET', '/foos/1', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => '/foos/1',
            'barJoined' => [
                '@type' => 'BarJoinedA',
                'y' => 'y_value',
            ],
        ]);
    }
}
