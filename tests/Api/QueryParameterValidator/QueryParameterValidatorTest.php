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

namespace ApiPlatform\Tests\Api\QueryParameterValidator;

use ApiPlatform\Api\FilterInterface;
use ApiPlatform\Api\QueryParameterValidator\QueryParameterValidator;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Exception\FilterValidationException;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Class QueryParameterValidatorTest.
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class QueryParameterValidatorTest extends TestCase
{
    use ProphecyTrait;

    private $testedInstance;
    private $filterLocatorProphecy;

    protected function setUp(): void
    {
        $this->filterLocatorProphecy = $this->prophesize(ContainerInterface::class);

        $this->testedInstance = new QueryParameterValidator(
            $this->filterLocatorProphecy->reveal()
        );
    }

    /**
     * unsafe method should not use filter validations.
     */
    public function testOnKernelRequestWithUnsafeMethod()
    {
        $request = [];

        $this->assertNull(
            $this->testedInstance->validateFilters(Dummy::class, [], $request)
        );
    }

    /**
     * If the tested filter is non-existent, then nothing should append.
     */
    public function testOnKernelRequestWithWrongFilter()
    {
        $request = [];

        $this->filterLocatorProphecy->has('some_inexistent_filter')->willReturn(false);

        $this->assertNull(
            $this->testedInstance->validateFilters(Dummy::class, ['some_inexistent_filter'], $request)
        );
    }

    /**
     * if the required parameter is not set, throw an FilterValidationException.
     */
    public function testOnKernelRequestWithRequiredFilterNotSet()
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

        $this->expectException(FilterValidationException::class);
        $this->expectExceptionMessage('Query parameter "required" is required');
        $this->testedInstance->validateFilters(Dummy::class, ['some_filter'], $request);
    }

    /**
     * if the required parameter is set, no exception should be throwned.
     */
    public function testOnKernelRequestWithRequiredFilter()
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

        $this->assertNull(
            $this->testedInstance->validateFilters(Dummy::class, ['some_filter'], $request)
        );
    }
}
