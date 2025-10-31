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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\TranslateValidationError;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ValidateParameterBeforeProvider;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\WithParameter;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ValidationTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [WithParameter::class, ValidateParameterBeforeProvider::class, TranslateValidationError::class];
    }

    public function testWithGroupFilter(): void
    {
        $response = self::createClient()->request('GET', 'with_parameters_collection');
        $this->assertArraySubset(['violations' => [['message' => 'This value should not be null.']]], $response->toArray(false));
        $response = self::createClient()->request('GET', 'with_parameters_collection?hydra');
        $this->assertResponseIsSuccessful();
    }

    /**
     * @param array<int,array{propertyPath: string, message: string}> $expectedViolations
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provideQueryStrings')]
    public function testValidation(string $queryString, array $expectedViolations): void
    {
        $response = self::createClient()->request('GET', 'validate_parameters?'.$queryString);
        $this->assertArraySubset([
            'violations' => $expectedViolations,
        ], $response->toArray(false));
    }

    public static function provideQueryStrings(): array
    {
        return [
            [
                'enumNotDeepObject[]=c&enumNotDeepObject[]=c',
                [
                    [
                        'propertyPath' => 'enumNotDeepObject', 'message' => 'The value you selected is not a valid choice.',
                    ],
                    [
                        'message' => 'This collection should contain only unique elements.',
                    ],
                ],
            ],
            [
                'enum[]=c&enum[]=c',
                [
                    [
                        'propertyPath' => 'enum[0]', 'message' => 'The value you selected is not a valid choice.',
                    ],
                    [
                        'propertyPath' => 'enum[1]', 'message' => 'The value you selected is not a valid choice.',
                    ],
                    [
                        'message' => 'This collection should contain only unique elements.',
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
                    ['propertyPath' => 'num', 'message' => 'This value should be between 1 and 3.'],
                ],
            ],
            [
                'numMultipleType=5',
                [
                    [
                        'propertyPath' => 'numMultipleType',
                        'message' => 'This value should satisfy at least one of the following constraints: [1] This value should be between 1 and 3. [2] Each element of this collection should satisfy its own set of constraints.',
                    ],
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
        self::createClient()->request('GET', 'validate_parameters?blank=f');
        $this->assertResponseIsSuccessful();
    }

    public function testValidateBeforeRead(): void
    {
        $response = self::createClient()->request('GET', 'query_parameter_validate_before_read');
        $this->assertArraySubset(['violations' => [['propertyPath' => 'search', 'message' => 'This value should not be blank.']]], $response->toArray(false));
    }

    public function testValidatePropertyPlaceholder(): void
    {
        self::createClient()->request('GET', 'query_parameter_validate_before_read?search=t&sort[id]=asc');
        $this->assertResponseIsSuccessful();
        $response = self::createClient()->request('GET', 'query_parameter_validate_before_read?search=t&sort[bar]=asc');
        $this->assertArraySubset([
            'violations' => [
                [
                    'propertyPath' => 'sort[bar]',
                    'message' => 'This field was not expected.',
                ],
            ],
        ], $response->toArray(false));
        $response = self::createClient()->request('GET', 'query_parameter_validate_before_read?search=t&sort[id]=foo');
        $this->assertArraySubset([
            'violations' => [
                [
                    'propertyPath' => 'sort[id]',
                    'message' => 'The value you selected is not a valid choice.',
                ],
            ],
        ], $response->toArray(false));
    }

    public function testValidateMessage(): void
    {
        $response = self::createClient()->request('GET', 'validate_parameters?int=test');
        $this->assertArraySubset([
            'detail' => 'int: This value should be of type integer.',
        ], $response->toArray(false));
    }

    public function testValidatePattern(): void
    {
        self::createClient()->request('GET', 'validate_parameters?pattern=2');
        $this->assertResponseIsSuccessful();
    }

    public function testTranslationValidation(): void
    {
        $res = self::createClient()->request('GET', 'translate_validation_error', ['headers' => ['accept-language' => 'es']]);
        $this->assertSame([
            ['propertyPath' => 'name', 'message' => 'Este valor no debería estar vacío.', 'code' => 'c1051bb4-d103-4f74-8988-acbcafc7fdc3'],
            ['propertyPath' => 'surname', 'message' => 'Este valor no debería ser nulo.', 'code' => 'ad32d13f-c3d4-423b-909a-857b961eb720'],
        ], $res->toArray(false)['violations']);
    }
}
