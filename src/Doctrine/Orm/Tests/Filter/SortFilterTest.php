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

namespace ApiPlatform\Doctrine\Orm\Tests\Filter;

use ApiPlatform\Doctrine\Orm\Filter\SortFilter;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SortFilterTest extends TestCase
{
    /**
     * @param list<string> $expectedNames
     */
    #[DataProvider('provideCastToArray')]
    public function testGetOpenApiParameters(?bool $castToArray, array $expectedNames): void
    {
        $parameter = new QueryParameter(key: 'orderName', property: 'name', castToArray: $castToArray);

        $openApiParameters = (new SortFilter())->getOpenApiParameters($parameter);
        $openApiParameters = \is_array($openApiParameters) ? $openApiParameters : [$openApiParameters];

        $this->assertSame($expectedNames, array_map(static fn (OpenApiParameter $openApiParameter): string => $openApiParameter->getName(), $openApiParameters));
    }

    public static function provideCastToArray(): iterable
    {
        yield 'default' => [null, ['orderName']];
        yield 'castToArray disabled' => [false, ['orderName']];
        yield 'castToArray enabled' => [true, ['orderName[]']];
    }
}
