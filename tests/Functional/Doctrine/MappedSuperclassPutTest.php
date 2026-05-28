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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyMappedSubclass;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class MappedSuperclassPutTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [DummyMappedSubclass::class];
    }

    public function testStandardPutOnEntityInheritedFromMappedSuperclass(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Not tested with mongodb.');
        }

        $this->recreateSchema([DummyMappedSubclass::class]);

        $manager = $this->getManager();
        $manager->persist(new DummyMappedSubclass());
        $manager->flush();

        $response = self::createClient()->request('PUT', '/dummy_mapped_subclasses/1', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['foo' => 'updated value'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/DummyMappedSubclass',
            '@id' => '/dummy_mapped_subclasses/1',
            '@type' => 'DummyMappedSubclass',
            'id' => 1,
            'foo' => 'updated value',
        ]);
    }
}
