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

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\FilterNestedTest\FilterCompany as DocumentFilterCompany;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\FilterNestedTest\FilterDepartment as DocumentFilterDepartment;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\FilterNestedTest\FilterEmployee as DocumentFilterEmployee;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FilterNestedTest\FilterCompany;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FilterNestedTest\FilterDepartment;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FilterNestedTest\FilterEmployee;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class SortFilterTest extends ApiTestCase
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

    protected function setUp(): void
    {
        $employeeClass = $this->isMongoDB() ? DocumentFilterEmployee::class : FilterEmployee::class;
        $departmentClass = $this->isMongoDB() ? DocumentFilterDepartment::class : FilterDepartment::class;
        $companyClass = $this->isMongoDB() ? DocumentFilterCompany::class : FilterCompany::class;

        $this->recreateSchema([$employeeClass, $departmentClass, $companyClass]);
        $this->loadFixtures();
    }

    public function testSortByName(): void
    {
        $response = self::createClient()->request('GET', '/filter_employees?orderName=asc');
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $names = array_map(static fn ($item) => $item['name'], $data['hydra:member']);

        $this->assertSame(['Alice', 'Bob', 'Charlie', 'David'], $names);
    }

    public function testSortByNameDesc(): void
    {
        $response = self::createClient()->request('GET', '/filter_employees?orderName=desc');
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $names = array_map(static fn ($item) => $item['name'], $data['hydra:member']);

        $this->assertSame(['David', 'Charlie', 'Bob', 'Alice'], $names);
    }

    public function testSortByNestedDepartmentName(): void
    {
        $response = self::createClient()->request('GET', '/filter_employees?orderDepartmentName=asc&orderName=asc');
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $names = array_map(static fn ($item) => $item['name'], $data['hydra:member']);

        // Engineering (Alice, Bob) then Sales (Charlie, David)
        $this->assertSame(['Alice', 'Bob', 'Charlie', 'David'], $names);
    }

    public function testSortByNestedDepartmentNameDesc(): void
    {
        $response = self::createClient()->request('GET', '/filter_employees?orderDepartmentName=desc&orderName=asc');
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $names = array_map(static fn ($item) => $item['name'], $data['hydra:member']);

        // Sales (Charlie, David) then Engineering (Alice, Bob)
        $this->assertSame(['Charlie', 'David', 'Alice', 'Bob'], $names);
    }

    public function testSortByHireDateNullsFirst(): void
    {
        $response = self::createClient()->request('GET', '/filter_employees?orderHireDate=asc');
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $names = array_map(static fn ($item) => $item['name'], $data['hydra:member']);

        // David has null hireDate -> first (NULLS_ALWAYS_FIRST)
        $this->assertSame('David', $names[0]);
    }

    public function testSortByHireDateNullsLast(): void
    {
        $response = self::createClient()->request('GET', '/filter_employees?orderHireDateNullsLast=asc');
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $names = array_map(static fn ($item) => $item['name'], $data['hydra:member']);

        // David has null hireDate -> last (NULLS_ALWAYS_LAST)
        $this->assertSame('David', $names[3]);
    }

    public function testSortByMultiHopCompanyName(): void
    {
        $response = self::createClient()->request('GET', '/filter_employees?orderCompanyName=asc');
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $names = array_map(static fn ($item) => $item['name'], $data['hydra:member']);

        // Acme Corp employees first, then Globex Inc employees
        $acmeNames = \array_slice($names, 0, 2);
        $globexNames = \array_slice($names, 2, 2);
        sort($acmeNames);
        sort($globexNames);
        $this->assertSame(['Alice', 'Bob'], $acmeNames);
        $this->assertSame(['Charlie', 'David'], $globexNames);
    }

    public function testSortByMultiHopCompanyNameDesc(): void
    {
        $response = self::createClient()->request('GET', '/filter_employees?orderCompanyName=desc');
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $names = array_map(static fn ($item) => $item['name'], $data['hydra:member']);

        // Globex Inc employees first, then Acme Corp employees
        $globexNames = \array_slice($names, 0, 2);
        $acmeNames = \array_slice($names, 2, 2);
        sort($globexNames);
        sort($acmeNames);
        $this->assertSame(['Charlie', 'David'], $globexNames);
        $this->assertSame(['Alice', 'Bob'], $acmeNames);
    }

    public function testLookupDeduplicationSortAndIriFilter(): void
    {
        // Get the engineering department IRI
        $response = self::createClient()->request('GET', '/filter_departments');
        $this->assertResponseIsSuccessful();
        $departments = $response->toArray()['hydra:member'];
        $engineeringIri = $departments[0]['@id'];

        // Apply both IRI filter on department and sort by department.name
        // This should NOT produce duplicate $lookup/$unwind stages
        $response = self::createClient()->request('GET', '/filter_employees?department='.$engineeringIri.'&orderDepartmentName=asc');
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $names = array_map(static fn ($item) => $item['name'], $data['hydra:member']);
        sort($names);

        $this->assertSame(['Alice', 'Bob'], $names);
    }

    public function testSortInvalidValueReturnsValidationError(): void
    {
        $response = self::createClient()->request('GET', '/filter_employees?orderName=invalid');
        $this->assertResponseStatusCodeSame(422);
    }

    public function testIriFilterOnDepartment(): void
    {
        $response = self::createClient()->request('GET', '/filter_departments');
        $this->assertResponseIsSuccessful();
        $departments = $response->toArray()['hydra:member'];
        $engineeringIri = $departments[0]['@id'];

        $response = self::createClient()->request('GET', '/filter_employees?department='.$engineeringIri);
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $names = array_map(static fn ($item) => $item['name'], $data['hydra:member']);
        sort($names);

        $this->assertSame(['Alice', 'Bob'], $names);
    }

    private function loadFixtures(): void
    {
        $manager = $this->getManager();

        $companyClass = $this->isMongoDB() ? DocumentFilterCompany::class : FilterCompany::class;
        $departmentClass = $this->isMongoDB() ? DocumentFilterDepartment::class : FilterDepartment::class;
        $employeeClass = $this->isMongoDB() ? DocumentFilterEmployee::class : FilterEmployee::class;

        $acme = new $companyClass();
        $acme->setName('Acme Corp');
        $manager->persist($acme);

        $globex = new $companyClass();
        $globex->setName('Globex Inc');
        $manager->persist($globex);

        $manager->flush();

        $engineering = new $departmentClass();
        $engineering->setName('Engineering');
        $engineering->setCompany($acme);
        $manager->persist($engineering);

        $sales = new $departmentClass();
        $sales->setName('Sales');
        $sales->setCompany($globex);
        $manager->persist($sales);

        $manager->flush();

        $alice = new $employeeClass();
        $alice->setName('Alice');
        $alice->setDepartment($engineering);
        $alice->setHireDate(new \DateTimeImmutable('2023-01-15'));
        $manager->persist($alice);

        $bob = new $employeeClass();
        $bob->setName('Bob');
        $bob->setDepartment($engineering);
        $bob->setHireDate(new \DateTimeImmutable('2023-06-01'));
        $manager->persist($bob);

        $charlie = new $employeeClass();
        $charlie->setName('Charlie');
        $charlie->setDepartment($sales);
        $charlie->setHireDate(new \DateTimeImmutable('2024-01-10'));
        $manager->persist($charlie);

        $david = new $employeeClass();
        $david->setName('David');
        $david->setDepartment($sales);
        $david->setHireDate(null);
        $manager->persist($david);

        $manager->flush();
    }
}
