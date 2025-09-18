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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\LinkParameterProviderResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\WithParameter;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Company;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Employee;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedOwnedDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class LinkProviderParameterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [WithParameter::class, Dummy::class, Employee::class, Company::class, LinkParameterProviderResource::class];
    }

    /**
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        $this->recreateSchema([Dummy::class, RelatedOwnedDummy::class, RelatedDummy::class, Employee::class, Company::class]);
    }

    public function testReadDummyProviderFromQueryParameter(): void
    {
        $container = static::getContainer();
        if ('mongodb' === $container->getParameter('kernel.environment')) {
            $this->markTestSkipped();
        }

        $manager = $this->getManager();
        $dummy = new Dummy();
        $dummy->setName('hi');
        $manager->persist($dummy);
        $manager->flush();

        $response = self::createClient()->request('GET', '/with_parameters_links?dummy='.$dummy->getId());
        $this->assertEquals('hi', $response->toArray()['name']);
        self::assertEquals(200, $response->getStatusCode());
        $response = self::createClient()->request('GET', '/with_parameters_links?dummy[id]='.$dummy->getId());
        $this->assertEquals('hi', $response->toArray()['name']);
        self::assertEquals(200, $response->getStatusCode());
    }

    public function testReadDummyIrisFromQueryParameter(): void
    {
        $container = static::getContainer();
        if ('mongodb' === $container->getParameter('kernel.environment')) {
            $this->markTestSkipped();
        }

        $manager = $this->getManager();
        $dummy = new Dummy();
        $dummy->setName('hi');
        $dummy2 = new Dummy();
        $dummy2->setName('ho');
        $manager->persist($dummy);
        $manager->persist($dummy2);
        $manager->flush();

        $response = self::createClient()->request('GET', \sprintf('/with_parameters_links?dummy[]=%s&dummy[]=%s', $dummy2->getId(), $dummy->getId()));
        $res = $response->toArray();
        $this->assertEquals('ho', $res['hydra:member'][0]['name']);
        $this->assertEquals('hi', $res['hydra:member'][1]['name']);
        self::assertEquals(200, $response->getStatusCode());
    }

    public function testReadDummyProviderFromQueryParameterNotFound(): void
    {
        $container = static::getContainer();
        if ('mongodb' === $container->getParameter('kernel.environment')) {
            $this->markTestSkipped();
        }
        $response = self::createClient()->request('GET', '/with_parameters_links?dummy=1');
        self::assertEquals(404, $response->getStatusCode());
    }

    public function testReadDummyProviderFromQueryParameterNoNotFound(): void
    {
        $container = static::getContainer();
        if ('mongodb' === $container->getParameter('kernel.environment')) {
            $this->markTestSkipped();
        }
        $response = self::createClient()->request('GET', '/with_parameters_links_no_not_found?dummy=1');
        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * See https://github.com/api-platform/core/issues/7061.
     */
    public function testLinkSecurityWithSlug(): void
    {
        $container = static::getContainer();
        if ('mongodb' === $container->getParameter('kernel.environment')) {
            $this->markTestSkipped();
        }

        $manager = $this->getManager();
        $employee = new Employee();
        $employee->setName('me');
        $dummy = new Company();
        $dummy->setName('Test');
        $employee->setCompany($dummy);
        $manager->persist($employee);
        $manager->persist($dummy);
        $manager->flush();

        self::createClient()->request('GET', '/companies-by-name/Test/employees');
        self::assertJsonContains([
            'hydra:member' => [
                ['company' => ['name' => 'Test']],
            ],
        ]);
        self::assertResponseStatusCodeSame(200);
    }

    /**
     * See https://github.com/api-platform/core/issues/7061.
     */
    public function testLinkSecurityWithConstraint(): void
    {
        $container = static::getContainer();
        if ('mongodb' === $container->getParameter('kernel.environment')) {
            $this->markTestSkipped();
        }

        $manager = $this->getManager();
        $employee = new Employee();
        $employee->setName('me');
        $dummy = new Company();
        $dummy->setName('Test');
        $employee->setCompany($dummy);
        $manager->persist($employee);
        $manager->persist($dummy);
        $manager->flush();

        $response = self::createClient()->request('GET', '/companies-by-name/NotTest/employees');
        self::assertEquals(422, $response->getStatusCode());
    }

    public function testUriVariableHasDummy(): void
    {
        $container = static::getContainer();
        if ('mongodb' === $container->getParameter('kernel.environment')) {
            $this->markTestSkipped();
        }

        $manager = $this->getManager();
        $dummy = new Dummy();
        $dummy->setName('hi');
        $manager->persist($dummy);
        $manager->flush();

        self::createClient()->request('GET', '/link_parameter_provider_resources/'.$dummy->getId());

        $this->assertJsonContains([
            'dummy' => '/dummies/1',
        ]);
    }
}
