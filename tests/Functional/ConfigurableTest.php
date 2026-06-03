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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FileConfigDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SingleFileConfigDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ConfigurableTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [FileConfigDummy::class, SingleFileConfigDummy::class];
    }

    private function seedFileConfigDummy(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('FileConfigDummy fixtures are ORM-only.');
        }

        $this->recreateSchema($this->getResources());

        $manager = $this->getManager();
        $entity = new FileConfigDummy();
        $entity->setName('ConfigDummy');
        $entity->setFoo('Foo');
        $manager->persist($entity);
        $manager->flush();
        $manager->clear();
    }

    public function testCollectionOfFileConfigDummies(): void
    {
        $this->seedFileConfigDummy();

        self::createClient()->request('GET', '/fileconfigdummies');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonEquals([
            '@context' => '/contexts/fileconfigdummy',
            '@id' => '/fileconfigdummies',
            '@type' => 'hydra:Collection',
            'hydra:member' => [
                [
                    '@id' => '/fileconfigdummies/1',
                    '@type' => 'fileconfigdummy',
                    'id' => 1,
                    'name' => 'ConfigDummy',
                    'foo' => 'Foo',
                ],
            ],
            'hydra:totalItems' => 1,
        ]);
    }

    public function testCollectionOfSingleFileConfig(): void
    {
        $this->recreateSchema($this->getResources());

        self::createClient()->request('GET', '/single_file_configs');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonEquals([
            '@context' => '/contexts/single_file_config',
            '@id' => '/single_file_configs',
            '@type' => 'hydra:Collection',
            'hydra:member' => [],
            'hydra:totalItems' => 0,
        ]);
    }

    public function testFileConfigDummyItem(): void
    {
        $this->seedFileConfigDummy();

        self::createClient()->request('GET', '/fileconfigdummies/1');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonEquals([
            '@context' => '/contexts/fileconfigdummy',
            '@id' => '/fileconfigdummies/1',
            '@type' => 'fileconfigdummy',
            'id' => 1,
            'name' => 'ConfigDummy',
            'foo' => 'Foo',
        ]);
    }
}
