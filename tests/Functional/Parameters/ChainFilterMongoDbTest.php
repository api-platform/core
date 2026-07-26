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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ChainFilterParameter;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ChainFilterMongoDbTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [ChainFilterParameter::class];
    }

    protected function setUp(): void
    {
        if (!$this->isMongoDB()) {
            $this->markTestSkipped('Only tested with mongodb.');
        }

        $this->recreateSchema([ChainFilterParameter::class]);
        $this->loadFixtures();
    }

    public function testExactMatch(): void
    {
        $response = self::createClient()->request('GET', '/chain_filter_parameters?quantity=10');
        $this->assertResponseIsSuccessful();
        $quantities = array_map(static fn ($m) => $m['quantity'], $response->toArray()['hydra:member']);
        $this->assertSame([10], $quantities);
    }

    public function testLessThan(): void
    {
        $response = self::createClient()->request('GET', '/chain_filter_parameters?quantity[lt]=10');
        $this->assertResponseIsSuccessful();
        $quantities = array_map(static fn ($m) => $m['quantity'], $response->toArray()['hydra:member']);
        sort($quantities);
        $this->assertSame([5], $quantities);
    }

    public function testGreaterThanOrEqual(): void
    {
        $response = self::createClient()->request('GET', '/chain_filter_parameters?quantity[gte]=10');
        $this->assertResponseIsSuccessful();
        $quantities = array_map(static fn ($m) => $m['quantity'], $response->toArray()['hydra:member']);
        sort($quantities);
        $this->assertSame([10, 15], $quantities);
    }

    public function testOpenApiParametersMergeBothFilters(): void
    {
        $response = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $openApiDoc = $response->toArray();

        $parameters = $openApiDoc['paths']['/chain_filter_parameters']['get']['parameters'];
        $parameterNames = array_column($parameters, 'name');

        foreach (['quantity', 'quantity[gt]', 'quantity[gte]', 'quantity[lt]', 'quantity[lte]', 'quantity[ne]'] as $expectedName) {
            $this->assertContains($expectedName, $parameterNames, \sprintf('Expected parameter "%s" in OpenAPI documentation', $expectedName));
        }
    }

    private function loadFixtures(): void
    {
        $manager = $this->getManager();

        foreach ([5, 10, 15] as $quantity) {
            $manager->persist(new ChainFilterParameter(quantity: $quantity));
        }

        $manager->flush();
    }
}
