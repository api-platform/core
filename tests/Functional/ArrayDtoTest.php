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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6211\ArrayPropertyDtoOperation;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ArrayDtoTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [ArrayPropertyDtoOperation::class];
    }

    public function testWithGroupFilter(): void
    {
        $response = self::createClient()->request('GET', '/array_property_dto_operations');
        $this->assertArraySubset(['name' => 'test', 'greetings' => [['name' => 'test2']]], $response->toArray());
    }
}
