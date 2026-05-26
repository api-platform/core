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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5587\Business;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5587\Employee;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class PutCollectionTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Business::class, Employee::class];
    }

    public function testPutReplacesEmbeddedCollection(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema($this->getResources());

        $client = self::createClient();
        $headers = ['Content-Type' => 'application/ld+json'];

        $client->request('POST', '/issue5584_employees', ['headers' => $headers, 'json' => ['name' => 'One']]);
        $client->request('POST', '/issue5584_employees', ['headers' => $headers, 'json' => ['name' => 'Two']]);
        $client->request('POST', '/issue5584_businesses', ['headers' => $headers, 'json' => ['name' => 'Business']]);

        $client->request('PUT', '/issue5584_businesses/1', [
            'headers' => $headers,
            'json' => [
                'name' => 'Business',
                'businessEmployees' => [
                    ['@id' => '/issue5584_employees/1', 'id' => 1],
                    ['@id' => '/issue5584_employees/2', 'id' => 2],
                ],
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'businessEmployees' => [
                ['name' => 'One'],
                ['name' => 'Two'],
            ],
        ]);
    }
}
