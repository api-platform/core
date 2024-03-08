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

namespace ApiPlatform\Tests\Parameter;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

final class ParameterTests extends ApiTestCase
{
    public function testWithGroupFilter(): void
    {
        $response = self::createClient()->request('GET', 'with_parameters/1?groups[]=b');
        $this->assertArraySubset(['b' => 'bar'], $response->toArray());
        $response = self::createClient()->request('GET', 'with_parameters/1?groups[]=b&groups[]=a');
        $this->assertArraySubset(['a' => 'foo', 'b' => 'bar'], $response->toArray());
    }

    public function testWithGroupProvider(): void
    {
        $response = self::createClient()->request('GET', 'with_parameters/1?group[]=b&group[]=a');
        $this->assertArraySubset(['a' => 'foo', 'b' => 'bar'], $response->toArray());
    }

    public function testWithServiceFilter(): void
    {
        $response = self::createClient()->request('GET', 'with_parameters/1?properties[]=a');
        $this->assertArraySubset(['a' => 'foo'], $response->toArray());
    }

    public function testWithServiceProvider(): void
    {
        $response = self::createClient()->request('GET', 'with_parameters/1?service=blabla');
        $this->assertArrayNotHasKey('a', $response->toArray());
    }

    public function testWithHeader(): void
    {
        self::createClient()->request('GET', 'with_parameters/1?service=blabla', ['headers' => ['auth' => 'foo']]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testHydraTemplate(): void
    {
        $response = self::createClient()->request('GET', 'with_parameters_collection');
        $this->assertArraySubset(['hydra:search' => [
            'hydra:template' => '/with_parameters_collection{?hydra}',
            'hydra:mapping' => [
                ['@type' => 'IriTemplateMapping', 'variable' => 'hydra', 'property' => 'a', 'required' => true],
            ],
        ]], $response->toArray());
    }
}
