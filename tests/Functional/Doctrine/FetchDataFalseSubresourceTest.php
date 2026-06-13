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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Company;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Employee;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class FetchDataFalseSubresourceTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Company::class, Employee::class];
    }

    public function testGetResourceFromSubresourceIriWithFetchDataFalseReturnsReference(): void
    {
        $container = static::getContainer();
        if ('mongodb' === $container->getParameter('kernel.environment')) {
            $this->markTestSkipped('getReference()/UnrecognizedIdentifierFields is ORM specific.');
        }

        $this->recreateSchema([Company::class, Employee::class]);

        $manager = $this->getManager();
        $company = new Company();
        $company->name = 'test';
        $manager->persist($company);

        $employees = [];
        for ($i = 0; $i < 3; ++$i) {
            $employee = new Employee();
            $employee->name = "Employee number $i";
            $employee->company = $company;
            $manager->persist($employee);
            $employees[] = $employee;
        }
        $manager->flush();

        $employee = $employees[1];
        $iri = \sprintf('/companies/%d/employees/%d', $company->getId(), $employee->getId());

        // fetch_data=false short-circuits to EntityManager::getReference(); the subresource IRI carries the
        // parent link "companyId" which is not an identifier of Employee and used to raise
        // Doctrine\ORM\Exception\UnrecognizedIdentifierFields ('companyId'). See #8124.
        $reference = $container->get('api_platform.iri_converter')->getResourceFromIri($iri, ['fetch_data' => false]);

        $this->assertInstanceOf(Employee::class, $reference);
        $this->assertSame($employee->getId(), $reference->getId());
    }
}
