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

namespace ApiPlatform\Tests\Functional\JsonLd;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5438\Contractor;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5438\Employee;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5438\Person;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class InheritanceIriTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Person::class, Contractor::class, Employee::class];
    }

    public function testCollectionItemsUseConcreteSubtypeIris(): void
    {
        $response = self::createClient()->request('GET', '/people_5438', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/contexts/People5438', $body['@context']);
        $this->assertSame('/people_5438', $body['@id']);
        $this->assertSame('hydra:Collection', $body['@type']);
        $this->assertSame(2, $body['hydra:totalItems']);

        $this->assertSame([
            [
                '@id' => '/contractor_5438/1',
                '@type' => 'Contractor',
                'id' => 1,
                'name' => 'a',
            ],
            [
                '@id' => '/employee_5438/2',
                '@type' => 'Employee',
                'id' => 2,
                'name' => 'b',
            ],
        ], $body['hydra:member']);
    }
}
