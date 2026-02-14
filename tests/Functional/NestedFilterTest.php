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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FilterNestedTest\Company;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FilterNestedTest\Department;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FilterNestedTest\Employee;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Tests for nested property filtering with IriFilter and UuidFilter.
 *
 * This test demonstrates the issue where IriFilter and UuidFilter don't properly
 * support nested properties (e.g., department.company).
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
        return [Company::class, Department::class, Employee::class];
    }

    public function testIriFilterWithDirectRelation(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not supported for this test');
        }

        $this->recreateSchema($this->getResources());
        [$company1, $company2, $dept1, $dept2, $emp1, $emp2] = $this->loadFixtures();

        // Test filtering by direct relation - this should work
        $response = self::createClient()->request('GET', '/employees?department=/departments/'.$dept1->getId());
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

        // Test filtering by nested relation - this is currently broken
        // Expected: Filter employees by their department's company
        $response = self::createClient()->request('GET', '/employees?departmentCompany=/companies/'.$company1->getId());
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

        // Test filtering by direct relation UUID - this should work
        $response = self::createClient()->request('GET', '/employees?departmentId='.$dept1->getId());
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

        // Test filtering by nested relation UUID - this is currently broken
        // Expected: Filter employees by their department's company UUID
        $response = self::createClient()->request('GET', '/employees?departmentCompanyId='.$company1->getId());
        $data = $response->toArray();

        $this->assertCount(1, $data['hydra:member'], 'Should find employee whose department belongs to company 1 by UUID');
        $this->assertEquals($emp1->getName(), $data['hydra:member'][0]['name']);
    }

    /**
     * @return array{Company, Company, Department, Department, Employee, Employee}
     */
    private function loadFixtures(): array
    {
        $container = static::getContainer();
        $registry = $container->get('doctrine');
        $manager = $registry->getManager();

        // Create two companies
        $company1 = new Company();
        $company1->setName('Acme Corp');
        $manager->persist($company1);

        $company2 = new Company();
        $company2->setName('TechStart Inc');
        $manager->persist($company2);

        // Create departments for each company
        $dept1 = new Department();
        $dept1->setName('Engineering');
        $dept1->setCompany($company1);
        $manager->persist($dept1);

        $dept2 = new Department();
        $dept2->setName('Sales');
        $dept2->setCompany($company2);
        $manager->persist($dept2);

        // Create employees in different departments
        $emp1 = new Employee();
        $emp1->setName('Alice');
        $emp1->setDepartment($dept1);
        $manager->persist($emp1);

        $emp2 = new Employee();
        $emp2->setName('Bob');
        $emp2->setDepartment($dept2);
        $manager->persist($emp2);

        $manager->flush();

        return [$company1, $company2, $dept1, $dept2, $emp1, $emp2];
    }
}
