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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FilterNestedTest\FilterCompany;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FilterNestedTest\FilterDepartment;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FilterNestedTest\FilterEmployee;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Tests for nested property filtering with IriFilter and UuidFilter.
 */
final class NestedFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [FilterCompany::class, FilterDepartment::class, FilterEmployee::class];
    }

    public function testIriFilterWithDirectRelation(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not supported for this test');
        }

        $this->recreateSchema($this->getResources());
        [$company1, $company2, $dept1, $dept2, $emp1, $emp2] = $this->loadFixtures();

        $response = self::createClient()->request('GET', '/filter_employees?department=/filter_departments/'.$dept1->getId());
        $data = $response->toArray();

        $this->assertCount(2, $data['hydra:member'], 'Should find employees in department 1');
        $names = array_map(static fn ($m) => $m['name'], $data['hydra:member']);
        $this->assertContains('Alice', $names);
        $this->assertContains('Charlie', $names);
    }

    public function testIriFilterWithNestedRelation(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not supported for this test');
        }

        $this->recreateSchema($this->getResources());
        [$company1, $company2, $dept1, $dept2, $emp1, $emp2] = $this->loadFixtures();

        $response = self::createClient()->request('GET', '/filter_employees?departmentCompany=/filter_companies/'.$company1->getId());
        $data = $response->toArray();

        $this->assertCount(2, $data['hydra:member'], 'Should find employees whose department belongs to company 1');
        $names = array_map(static fn ($m) => $m['name'], $data['hydra:member']);
        $this->assertContains('Alice', $names);
        $this->assertContains('Charlie', $names);
    }

    public function testUuidFilterWithDirectRelation(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not supported for this test');
        }

        $this->recreateSchema($this->getResources());
        [$company1, $company2, $dept1, $dept2, $emp1, $emp2] = $this->loadFixtures();

        $response = self::createClient()->request('GET', '/filter_employees?departmentId='.$dept1->getId());
        $data = $response->toArray();

        $this->assertCount(2, $data['hydra:member'], 'Should find employees in department 1 by UUID');
        $names = array_map(static fn ($m) => $m['name'], $data['hydra:member']);
        $this->assertContains('Alice', $names);
        $this->assertContains('Charlie', $names);
    }

    public function testUuidFilterWithNestedRelation(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not supported for this test');
        }

        $this->recreateSchema($this->getResources());
        [$company1, $company2, $dept1, $dept2, $emp1, $emp2] = $this->loadFixtures();

        $response = self::createClient()->request('GET', '/filter_employees?departmentCompanyId='.$company1->getId());
        $data = $response->toArray();

        $this->assertCount(2, $data['hydra:member'], 'Should find employees whose department belongs to company 1 by UUID');
        $names = array_map(static fn ($m) => $m['name'], $data['hydra:member']);
        $this->assertContains('Alice', $names);
        $this->assertContains('Charlie', $names);
    }

    public function testOrderFilterWithNestedRelation(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not supported for this test');
        }

        $this->recreateSchema($this->getResources());
        $this->loadFixtures();

        // Order by department.name ASC — Engineering < Sales
        $response = self::createClient()->request('GET', '/filter_employees?orderDepartmentName=asc');
        $data = $response->toArray();

        $this->assertCount(3, $data['hydra:member']);
        // Engineering employees first (Alice, Charlie), then Sales (Bob)
        $this->assertEquals('Bob', $data['hydra:member'][2]['name']);

        // Order by department.name DESC — Sales > Engineering
        $response = self::createClient()->request('GET', '/filter_employees?orderDepartmentName=desc');
        $data = $response->toArray();

        $this->assertCount(3, $data['hydra:member']);
        $this->assertEquals('Bob', $data['hydra:member'][0]['name']);
    }

    public function testOrderFilterWithDirectProperty(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not supported for this test');
        }

        $this->recreateSchema($this->getResources());
        $this->loadFixtures();

        // Order by name ASC — Alice < Bob < Charlie
        $response = self::createClient()->request('GET', '/filter_employees?orderName=asc');
        $data = $response->toArray();

        $this->assertCount(3, $data['hydra:member']);
        $this->assertEquals('Alice', $data['hydra:member'][0]['name']);
        $this->assertEquals('Bob', $data['hydra:member'][1]['name']);
        $this->assertEquals('Charlie', $data['hydra:member'][2]['name']);

        // Order by name DESC — Charlie > Bob > Alice
        $response = self::createClient()->request('GET', '/filter_employees?orderName=desc');
        $data = $response->toArray();

        $this->assertCount(3, $data['hydra:member']);
        $this->assertEquals('Charlie', $data['hydra:member'][0]['name']);
        $this->assertEquals('Bob', $data['hydra:member'][1]['name']);
        $this->assertEquals('Alice', $data['hydra:member'][2]['name']);
    }

    public function testSortFilterNullsAlwaysFirst(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not supported for this test');
        }

        $this->recreateSchema($this->getResources());
        $this->loadFixtures();

        // ASC with nulls_always_first — Charlie (null) first, then Alice (2024-01), then Bob (2024-06)
        $response = self::createClient()->request('GET', '/filter_employees?orderHireDate=asc');
        $data = $response->toArray();

        $this->assertCount(3, $data['hydra:member']);
        $this->assertEquals('Charlie', $data['hydra:member'][0]['name']);
        $this->assertEquals('Alice', $data['hydra:member'][1]['name']);
        $this->assertEquals('Bob', $data['hydra:member'][2]['name']);

        // DESC with nulls_always_first — Charlie (null) first, then Bob (2024-06), then Alice (2024-01)
        $response = self::createClient()->request('GET', '/filter_employees?orderHireDate=desc');
        $data = $response->toArray();

        $this->assertCount(3, $data['hydra:member']);
        $this->assertEquals('Charlie', $data['hydra:member'][0]['name']);
        $this->assertEquals('Bob', $data['hydra:member'][1]['name']);
        $this->assertEquals('Alice', $data['hydra:member'][2]['name']);
    }

    public function testSortFilterNullsAlwaysLast(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not supported for this test');
        }

        $this->recreateSchema($this->getResources());
        $this->loadFixtures();

        // ASC with nulls_always_last — Alice (2024-01), Bob (2024-06), then Charlie (null)
        $response = self::createClient()->request('GET', '/filter_employees?orderHireDateNullsLast=asc');
        $data = $response->toArray();

        $this->assertCount(3, $data['hydra:member']);
        $this->assertEquals('Alice', $data['hydra:member'][0]['name']);
        $this->assertEquals('Bob', $data['hydra:member'][1]['name']);
        $this->assertEquals('Charlie', $data['hydra:member'][2]['name']);

        // DESC with nulls_always_last — Bob (2024-06), Alice (2024-01), then Charlie (null)
        $response = self::createClient()->request('GET', '/filter_employees?orderHireDateNullsLast=desc');
        $data = $response->toArray();

        $this->assertCount(3, $data['hydra:member']);
        $this->assertEquals('Bob', $data['hydra:member'][0]['name']);
        $this->assertEquals('Alice', $data['hydra:member'][1]['name']);
        $this->assertEquals('Charlie', $data['hydra:member'][2]['name']);
    }

    private function loadFixtures(): array
    {
        $container = static::getContainer();
        $registry = $container->get('doctrine');
        $manager = $registry->getManager();

        $company1 = new FilterCompany();
        $company1->setName('Acme Corp');
        $manager->persist($company1);

        $company2 = new FilterCompany();
        $company2->setName('TechStart Inc');
        $manager->persist($company2);

        $manager->flush();

        $dept1 = new FilterDepartment();
        $dept1->setName('Engineering');
        $dept1->setCompany($company1);
        $manager->persist($dept1);

        $dept2 = new FilterDepartment();
        $dept2->setName('Sales');
        $dept2->setCompany($company2);
        $manager->persist($dept2);

        $manager->flush();

        $emp1 = new FilterEmployee();
        $emp1->setName('Alice');
        $emp1->setDepartment($dept1);
        $emp1->setHireDate(new \DateTimeImmutable('2024-01-15'));
        $manager->persist($emp1);

        $emp2 = new FilterEmployee();
        $emp2->setName('Bob');
        $emp2->setDepartment($dept2);
        $emp2->setHireDate(new \DateTimeImmutable('2024-06-01'));
        $manager->persist($emp2);

        $emp3 = new FilterEmployee();
        $emp3->setName('Charlie');
        $emp3->setDepartment($dept1);
        // hireDate left null
        $manager->persist($emp3);

        $manager->flush();

        return [$company1, $company2, $dept1, $dept2, $emp1, $emp2, $emp3];
    }
}
