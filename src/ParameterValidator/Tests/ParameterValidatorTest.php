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

use ApiPlatform\Exception\FilterValidationException;
use ApiPlatform\Metadata\FilterInterface;
use ApiPlatform\ParameterValidator\Exception\ValidationException;
use ApiPlatform\ParameterValidator\ParameterValidator;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
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
}
