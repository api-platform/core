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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7354\BooleanQueryParameter;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class Issue7354Test extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [BooleanQueryParameter::class];
    }

    public function testBooleanQueryParameterDefaultOverride(): void
    {
        self::createClient()->request('GET', '/issue7354_boolean_query_parameters?booleanParameter=false');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['booleanParameter' => false]);
    }

    public function testBooleanQueryParameterDefaultNotOverride(): void
    {
        self::createClient()->request('GET', '/issue7354_boolean_query_parameters?booleanParameter=true');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['booleanParameter' => true]);
    }

    public function testBooleanQueryParameterDefaultValue(): void
    {
        self::createClient()->request('GET', '/issue7354_boolean_query_parameters');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['booleanParameter' => true]);
    }
}
