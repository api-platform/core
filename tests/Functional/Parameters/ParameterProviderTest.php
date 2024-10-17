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

final class ParameterProviderTest extends ApiTestCase
{
    public function testMultipleParameterProviderShouldChangeTheOperation(): void
    {
        $response = self::createClient()->request('GET', 'issue6673_multiple_parameter_provider?a=1&b=2', ['headers' => ['accept' => 'application/json']]);
        $this->assertArraySubset(['a' => '1', 'b' => '2'], $response->toArray());
    }
}
