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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6673\MutlipleParameterProvider;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ParameterProviderTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [MutlipleParameterProvider::class];
    }

    public function testMultipleParameterProviderShouldChangeTheOperation(): void
    {
        $response = self::createClient()->request('GET', 'issue6673_multiple_parameter_provider?a=1&b=2', ['headers' => ['accept' => 'application/json']]);
        $this->assertArraySubset(['a' => '1', 'b' => '2'], $response->toArray());
    }
}
