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

final class ValidationTests extends ApiTestCase
{
    public function testWithGroupFilter(): void
    {
        $response = self::createClient()->request('GET', 'with_parameters_collection');
        $this->assertArraySubset(['violations' => [['propertyPath' => 'a', 'message' => 'The parameter "hydra" is required.']]], $response->toArray(false));
        $response = self::createClient()->request('GET', 'with_parameters_collection?hydra');
        $this->assertResponseIsSuccessful();
    }

    /**
     * @dataProvider provideQueryStrings
     *
     * @param array<int,array{propertyPath: string, message: string}> $expectedViolations
     */
    public function testValidation(string $queryString, array $expectedViolations): void
    {
        $response = self::createClient()->request('GET', 'validate_parameters?'.$queryString);
        $this->assertArraySubset([
            'violations' => $expectedViolations,
        ], $response->toArray(false));
    }

    public function provideQueryStrings(): array
    {
        return [
            [
                'enum[]=c&enum[]=c',
                [
                    [
                        'propertyPath' => 'enum', 'message' => 'This collection should contain only unique elements.',
                    ],
                    [
                        'propertyPath' => 'enum', 'message' => 'The value you selected is not a valid choice.',
                    ],
                ],
            ],
            [
                'blank=',
                [
                    [
                        'propertyPath' => 'blank', 'message' => 'This value should not be blank.',
                    ],
                ],
            ],
            [
                'length=toolong',
                [
                    ['propertyPath' => 'length', 'message' => 'This value is too long. It should have 1 character or less.'],
                ],
            ],
            [
                'multipleOf=3',
                [
                    ['propertyPath' => 'multipleOf', 'message' => 'This value should be a multiple of 2.'],
                ],
            ],
            [
                'pattern=no',
                [
                    ['propertyPath' => 'pattern', 'message' => 'This value is not valid.'],
                ],
            ],
            [
                'array[]=1',
                [
                    ['propertyPath' => 'array', 'message' => 'This collection should contain 2 elements or more.'],
                ],
            ],
            [
                'num=5',
                [
                    ['propertyPath' => 'num', 'message' => 'This value should be less than or equal to 3.'],
                ],
            ],
            [
                'exclusiveNum=5',
                [
                    ['propertyPath' => 'exclusiveNum', 'message' => 'This value should be less than 3.'],
                ],
            ],
        ];
    }

    public function testBlank(): void
    {
        $response = self::createClient()->request('GET', 'validate_parameters?blank=f');
        $this->assertResponseIsSuccessful();
    }
}
