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

namespace ApiPlatform\ParameterValidator\Tests;

use ApiPlatform\Metadata\FilterInterface;
use ApiPlatform\ParameterValidator\Exception\ValidationException;
use ApiPlatform\ParameterValidator\ParameterValidator;
use ApiPlatform\ParameterValidator\Tests\Fixtures\Dummy;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;

/**
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class ParameterValidatorTest extends TestCase
{
    use ProphecyTrait;

    private ParameterValidator $testedInstance;
    private ObjectProphecy $filterLocatorProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->filterLocatorProphecy = $this->prophesize(ContainerInterface::class);

        $this->testedInstance = new ParameterValidator(
            $this->filterLocatorProphecy->reveal()
        );
    }

    /**
     * unsafe method should not use filter validations.
     *
     * @doesNotPerformAssertions
     */
    public function testOnKernelRequestWithUnsafeMethod(): void
    {
        $request = [];

        $this->testedInstance->validateFilters(Dummy::class, [], $request);
    }

    /**
     * If the tested filter is non-existent, then nothing should append.
     *
     * @doesNotPerformAssertions
     */
    public function testOnKernelRequestWithWrongFilter(): void
    {
        $request = [];

        $this->filterLocatorProphecy->has('some_inexistent_filter')->willReturn(false);
        $this->testedInstance->validateFilters(Dummy::class, ['some_inexistent_filter'], $request);
    }

    /**
     * if the required parameter is not set, throw an FilterValidationException.
     */
    public function testOnKernelRequestWithRequiredFilterNotSet(): void
    {
        $request = [];

        $filterProphecy = $this->prophesize(FilterInterface::class);
        $filterProphecy
            ->getDescription(Dummy::class)
            ->shouldBeCalled()
            ->willReturn([
                'required' => [
                    'required' => true,
                ],
            ]);
        $this->filterLocatorProphecy
            ->has('some_filter')
            ->shouldBeCalled()
            ->willReturn(true);
        $this->filterLocatorProphecy
            ->get('some_filter')
            ->shouldBeCalled()
            ->willReturn($filterProphecy->reveal());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Query parameter "required" is required');
        $this->testedInstance->validateFilters(Dummy::class, ['some_filter'], $request);
    }

    /**
     * if the required parameter is set, no exception should be throwned.
     */
    public function testOnKernelRequestWithRequiredFilter(): void
    {
        $request = ['required' => 'foo'];

        $this->filterLocatorProphecy
            ->has('some_filter')
            ->shouldBeCalled()
            ->willReturn(true);
        $filterProphecy = $this->prophesize(FilterInterface::class);
        $filterProphecy
            ->getDescription(Dummy::class)
            ->shouldBeCalled()
            ->willReturn([
                'required' => [
                    'required' => true,
                ],
            ]);
        $this->filterLocatorProphecy
            ->get('some_filter')
            ->shouldBeCalled()
            ->willReturn($filterProphecy->reveal());

        $this->testedInstance->validateFilters(Dummy::class, ['some_filter'], $request);
    }

    /**
     * @dataProvider provideValidateNonScalarsCases
     */
    public function testValidateNonScalars(array $request, array $description, string|null $exceptionMessage): void
    {
        $this->filterLocatorProphecy
            ->has('some_filter')
            ->shouldBeCalled()
            ->willReturn(true);

        $filterProphecy = $this->prophesize(FilterInterface::class);
        $filterProphecy
            ->getDescription(Dummy::class)
            ->shouldBeCalled()
            ->willReturn($description);

        $this->filterLocatorProphecy
            ->get('some_filter')
            ->shouldBeCalled()
            ->willReturn($filterProphecy->reveal());

        if (null !== $exceptionMessage) {
            $this->expectException(ValidationException::class);
            $this->expectExceptionMessageMatches('#^'.preg_quote($exceptionMessage).'$#');
        }

        $this->testedInstance->validateFilters(Dummy::class, ['some_filter'], $request);
    }

    public function provideValidateNonScalarsCases(): iterable
    {
        $enum = ['parameter' => [
            'openapi' => [
                'enum' => ['foo', 'bar'],
            ],
        ]];

        yield 'valid values should not throw' => [
            ['parameter' => 'bar'], $enum, null,
        ];

        yield 'invalid single scalar should still throw' => [
            ['parameter' => 'baz'], $enum, 'Query parameter "parameter" must be one of "foo, bar"',
        ];

        yield 'invalid single value in a non scalar should throw' => [
            ['parameter' => ['baz']], $enum, 'Query parameter "parameter" must be one of "foo, bar"',
        ];

        yield 'multiple invalid values in a non scalar should throw' => [
            ['parameter' => ['baz', 'boo']], $enum, 'Query parameter "parameter" must be one of "foo, bar"',
        ];

        yield 'combination of valid and invalid values should throw' => [
            ['parameter' => ['foo', 'boo']], $enum, 'Query parameter "parameter" must be one of "foo, bar"',
        ];

        yield 'duplicate valid values should throw' => [
            ['parameter' => ['foo', 'foo']],
            ['parameter' => [
                'openapi' => [
                    'enum' => ['foo', 'bar'],
                    'uniqueItems' => true,
                ],
            ]],
            'Query parameter "parameter" must contain unique values',
        ];

        yield 'if less values than allowed is provided it should throw' => [
            ['parameter' => ['foo']],
            ['parameter' => [
                'openapi' => [
                    'enum' => ['foo', 'bar'],
                    'minItems' => 2,
                ],
            ]],
            'Query parameter "parameter" must contain more than 2 values', // todo: this message does seem accurate
        ];

        yield 'if more values than allowed is provided it should throw' => [
            ['parameter' => ['foo', 'bar', 'baz']],
            ['parameter' => [
                'openapi' => [
                    'enum' => ['foo', 'bar', 'baz'],
                    'maxItems' => 2,
                ],
            ]],
            'Query parameter "parameter" must contain less than 2 values', // todo: this message does seem accurate
        ];

        yield 'for array constraints all violation should be reported' => [
            ['parameter' => ['foo', 'foo', 'bar']],
            ['parameter' => [
                'openapi' => [
                    'enum' => ['foo', 'bar'],
                    'uniqueItems' => true,
                    'minItems' => 1,
                    'maxItems' => 2,
                ],
            ]],
            implode(\PHP_EOL, [
                'Query parameter "parameter" must contain less than 2 values',
                'Query parameter "parameter" must contain unique values',
            ]),
        ];
    }
}
