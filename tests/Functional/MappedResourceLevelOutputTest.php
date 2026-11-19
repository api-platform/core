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

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MappedResourceLevelOutput;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MappedResourceLevelOutputEntity;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class MappedResourceLevelOutputTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;
    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [MappedResourceLevelOutput::class];
    }

    public function testDeleteRemovesEntityWhenOutputIsDeclared(): void
    {
        $id = $this->persistEntity('before', 'kept');

        self::createClient()->request('DELETE', '/mapped_resource_level_outputs/'.$id);

        $this->assertResponseIsSuccessful();
        $this->assertNull($this->getManager()->getRepository(MappedResourceLevelOutputEntity::class)->find($id));
    }

    public function testPatchKeepsUnsentFieldsWhenOutputIsDeclared(): void
    {
        $id = $this->persistEntity('before', 'kept');

        self::createClient()->request('PATCH', '/mapped_resource_level_outputs/'.$id, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['name' => 'after'],
        ]);

        $this->assertResponseIsSuccessful();
        $entity = $this->getManager()->getRepository(MappedResourceLevelOutputEntity::class)->find($id);
        $this->assertSame('after', $entity->getName());
        $this->assertSame('kept', $entity->getDescription());
    }

    private function persistEntity(string $name, string $description): int
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not tested.');
        }

        $this->recreateSchema([MappedResourceLevelOutputEntity::class]);
        $manager = $this->getManager();
        $entity = new MappedResourceLevelOutputEntity();
        $entity->setName($name);
        $entity->setDescription($description);
        $manager->persist($entity);
        $manager->flush();
        $id = $entity->getId();
        $manager->clear();

        return $id;
    }
}
