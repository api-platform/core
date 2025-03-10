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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\StrictParameters;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class StrictParametersTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [StrictParameters::class];
    }

    public function testErrorAsParameterIsNotAllowed(): void
    {
        self::createClient()->request('GET', 'strict_query_parameters?bar=test');
        $this->assertJsonContains(['detail' => 'Parameter not supported']);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testCorrectParameters(): void
    {
        self::createClient()->request('GET', 'strict_query_parameters');
        $this->assertResponseStatusCodeSame(200);
        self::createClient()->request('GET', 'strict_query_parameters?foo=test');
        $this->assertResponseStatusCodeSame(200);
    }
}
