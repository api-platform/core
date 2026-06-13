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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Company;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Employee;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class CrudUriVariablesTest extends ApiTestCase
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

    protected function setUp(): void
    {
        $this->recreateSchema([Company::class, Employee::class]);
    }

    private function seed(): void
    {
        $client = self::createClient();
        $headers = ['Content-Type' => 'application/ld+json'];
        $client->request('POST', '/companies', ['headers' => $headers, 'json' => ['name' => 'Foo Company 1']]);
        $client->request('POST', '/companies', ['headers' => $headers, 'json' => ['name' => 'Foo Company 2']]);
        $client->request('POST', '/employees', ['headers' => $headers, 'json' => ['name' => 'foo', 'company' => '/companies/1']]);
        $client->request('POST', '/employees', ['headers' => $headers, 'json' => ['name' => 'foo2', 'company' => '/companies/2']]);
        $client->request('POST', '/employees', ['headers' => $headers, 'json' => ['name' => 'foo3', 'company' => '/companies/2']]);
    }

    public function testCreateCompany(): void
    {
        self::createClient()->request('POST', '/companies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'Foo Company 1'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Location', '/companies/1.jsonld');
        $this->assertResponseHeaderSame('Location', '/companies/1');
        $this->assertJsonEquals([
            '@context' => '/contexts/Company',
            '@id' => '/companies/1',
            '@type' => 'Company',
            'id' => 1,
            'name' => 'Foo Company 1',
            'employees' => [],
        ]);
    }

    public function testCreateSecondCompany(): void
    {
        $client = self::createClient();
        $client->request('POST', '/companies', ['headers' => ['Content-Type' => 'application/ld+json'], 'json' => ['name' => 'Foo Company 1']]);
        $client->request('POST', '/companies', ['headers' => ['Content-Type' => 'application/ld+json'], 'json' => ['name' => 'Foo Company 2']]);

        $this->assertResponseStatusCodeSame(201);
    }

    public function testCreateEmployeeReturnsScopedUri(): void
    {
        $client = self::createClient();
        $client->request('POST', '/companies', ['headers' => ['Content-Type' => 'application/ld+json'], 'json' => ['name' => 'Foo Company 1']]);
        $client->request('POST', '/employees', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'foo', 'company' => '/companies/1'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Location', '/companies/1/employees/1.jsonld');
        $this->assertResponseHeaderSame('Location', '/companies/1/employees/1');
        $this->assertJsonEquals([
            '@context' => '/contexts/Employee',
            '@id' => '/companies/1/employees/1',
            '@type' => 'Employee',
            'id' => 1,
            'name' => 'foo',
            'company' => '/companies/1',
        ]);
    }

    public function testGetEmployeesCollectionByCompany(): void
    {
        $this->seed();

        self::createClient()->request('GET', '/companies/2/employees', [
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonEquals([
            '@context' => '/contexts/Employee',
            '@id' => '/companies/2/employees',
            '@type' => 'hydra:Collection',
            'hydra:member' => [
                [
                    '@id' => '/companies/2/employees/2',
                    '@type' => 'Employee',
                    'name' => 'foo2',
                    'company' => ['@id' => '/companies/2', '@type' => 'Company', 'name' => 'Foo Company 2'],
                ],
                [
                    '@id' => '/companies/2/employees/3',
                    '@type' => 'Employee',
                    'name' => 'foo3',
                    'company' => ['@id' => '/companies/2', '@type' => 'Company', 'name' => 'Foo Company 2'],
                ],
            ],
            'hydra:totalItems' => 2,
        ]);
    }

    public function testGetCompanyOfEmployee(): void
    {
        $this->seed();

        self::createClient()->request('GET', '/employees/1/company', [
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonEquals([
            '@context' => '/contexts/Company',
            '@id' => '/employees/1/company',
            '@type' => 'Company',
            'id' => 1,
            'name' => 'Foo Company 1',
            'employees' => [],
        ]);
    }

    public function testGetEmployeeWithCompanyUriVariable(): void
    {
        $this->seed();

        self::createClient()->request('GET', '/companies/1/employees/1', [
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonEquals([
            '@context' => '/contexts/Employee',
            '@id' => '/companies/1/employees/1',
            '@type' => 'Employee',
            'id' => 1,
            'name' => 'foo',
            'company' => '/companies/1',
        ]);
    }

    public function testWrongCompanyContextReturns404(): void
    {
        $this->seed();

        self::createClient()->request('GET', '/companies/1/employees/2', [
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testGraphQLCompaniesAndEmployees(): void
    {
        $this->seed();

        $response = self::createClient()->request('POST', '/graphql', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['query' => '{ companies { edges { node { name employees { edges { node { name } } } } } } }'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $data = $response->toArray();
        $companies = $data['data']['companies']['edges'];
        $this->assertSame('Foo Company 1', $companies[0]['node']['name']);
        $this->assertCount(1, $companies[0]['node']['employees']['edges']);
        $this->assertSame('foo', $companies[0]['node']['employees']['edges'][0]['node']['name']);
        $this->assertSame('Foo Company 2', $companies[1]['node']['name']);
        $this->assertCount(2, $companies[1]['node']['employees']['edges']);
        $this->assertSame('foo2', $companies[1]['node']['employees']['edges'][0]['node']['name']);
        $this->assertSame('foo3', $companies[1]['node']['employees']['edges'][1]['node']['name']);
    }
}
