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

        $this->assertCount(1, $data['hydra:member'], 'Should find employee in department 1');
        $this->assertEquals($emp1->getName(), $data['hydra:member'][0]['name']);
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

        $this->assertCount(1, $data['hydra:member'], 'Should find employee whose department belongs to company 1');
        $this->assertEquals($emp1->getName(), $data['hydra:member'][0]['name']);
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

        $this->assertCount(1, $data['hydra:member'], 'Should find employee in department 1 by UUID');
        $this->assertEquals($emp1->getName(), $data['hydra:member'][0]['name']);
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

        $this->assertCount(1, $data['hydra:member'], 'Should find employee whose department belongs to company 1 by UUID');
        $this->assertEquals($emp1->getName(), $data['hydra:member'][0]['name']);
    }

    public function testOrderFilterWithNestedRelation(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not supported for this test');
        }

        $this->recreateSchema($this->getResources());
        $this->loadFixtures();

        // Order by department.name ASC — Engineering < Sales, so Alice first
        $response = self::createClient()->request('GET', '/filter_employees?orderDepartmentName=asc');
        $data = $response->toArray();

        $this->assertCount(2, $data['hydra:member']);
        $this->assertEquals('Alice', $data['hydra:member'][0]['name']);
        $this->assertEquals('Bob', $data['hydra:member'][1]['name']);

        // Order by department.name DESC — Sales > Engineering, so Bob first
        $response = self::createClient()->request('GET', '/filter_employees?orderDepartmentName=desc');
        $data = $response->toArray();

        $this->assertCount(2, $data['hydra:member']);
        $this->assertEquals('Bob', $data['hydra:member'][0]['name']);
        $this->assertEquals('Alice', $data['hydra:member'][1]['name']);
    }

    public function testOrderFilterWithDirectProperty(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not supported for this test');
        }

        $this->recreateSchema($this->getResources());
        $this->loadFixtures();

        // Order by name ASC — Alice < Bob
        $response = self::createClient()->request('GET', '/filter_employees?orderName=asc');
        $data = $response->toArray();

        $this->assertCount(2, $data['hydra:member']);
        $this->assertEquals('Alice', $data['hydra:member'][0]['name']);
        $this->assertEquals('Bob', $data['hydra:member'][1]['name']);

        // Order by name DESC — Bob > Alice
        $response = self::createClient()->request('GET', '/filter_employees?orderName=desc');
        $data = $response->toArray();

        $this->assertCount(2, $data['hydra:member']);
        $this->assertEquals('Bob', $data['hydra:member'][0]['name']);
        $this->assertEquals('Alice', $data['hydra:member'][1]['name']);
    }

    /**
     * @return array{FilterCompany, FilterCompany, FilterDepartment, FilterDepartment, FilterEmployee, FilterEmployee}
     */
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
        $manager->persist($emp1);

        $emp2 = new FilterEmployee();
        $emp2->setName('Bob');
        $emp2->setDepartment($dept2);
        $manager->persist($emp2);

        $manager->flush();

        return [$company1, $company2, $dept1, $dept2, $emp1, $emp2];
    }
}
