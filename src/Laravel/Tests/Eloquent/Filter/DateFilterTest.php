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

namespace ApiPlatform\Laravel\Tests\Eloquent\Filter;

use ApiPlatform\Laravel\Eloquent\Filter\DateFilter;
use ApiPlatform\Metadata\QueryParameter;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\TestCase;

class DateFilterTest extends TestCase
{
    public function testOperator(): void
    {
        $f = new DateFilter();
        $builder = $this->createStub(Builder::class);
        $this->assertEquals($builder, $f->apply($builder, ['neq' => '2020-02-02'], new QueryParameter(key: 'date', property: 'date')));
    }
}
