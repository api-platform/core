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

namespace ApiPlatform\Tests\Functional\Parameters;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\TreeDepartment as DocumentTreeDepartment;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\TreePerson as DocumentTreePerson;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\TreeDepartment;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\TreePerson;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ODM\MongoDB\MongoDBException;

final class NestedSelfReferenceFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [TreePerson::class, TreeDepartment::class];
    }

    public function testFilterOnTheSameAssociationTwice(): void
    {
        $res = $this->createClient()->request('GET', '/tree_people?hasManagerOfManager=true')->toArray();

        $this->assertSame(['Carol'], array_map(static fn (array $person): string => $person['name'], $res['member']));
    }

    public function testFilterOnAnAssociationContainedInThePreviousOne(): void
    {
        $res = $this->createClient()->request('GET', '/tree_people?hasDepartmentPart=true')->toArray();

        $this->assertSame(['Bob'], array_map(static fn (array $person): string => $person['name'], $res['member']));
    }

    /**
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        $this->recreateSchema($this->isMongoDB() ? [DocumentTreePerson::class, DocumentTreeDepartment::class] : [TreePerson::class, TreeDepartment::class]);
        $this->loadFixtures();
    }

    /**
     * @throws \Throwable
     * @throws MongoDBException
     */
    private function loadFixtures(): void
    {
        $manager = $this->getManager();
        $class = $this->isMongoDB() ? DocumentTreePerson::class : TreePerson::class;
        $departmentClass = $this->isMongoDB() ? DocumentTreeDepartment::class : TreeDepartment::class;

        $engineering = new $departmentClass();
        $engineering->name = 'Engineering';
        $manager->persist($engineering);

        $backend = new $departmentClass();
        $backend->name = 'Backend';
        $backend->part = $engineering;
        $manager->persist($backend);

        $alice = new $class();
        $alice->name = 'Alice';
        $manager->persist($alice);

        $bob = new $class();
        $bob->name = 'Bob';
        $bob->manager = $alice;
        $bob->department = $backend;
        $manager->persist($bob);

        $carol = new $class();
        $carol->name = 'Carol';
        $carol->manager = $bob;
        $carol->department = $engineering;
        $manager->persist($carol);

        $manager->flush();
    }
}
