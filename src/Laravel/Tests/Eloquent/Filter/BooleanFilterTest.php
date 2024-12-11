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

use ApiPlatform\Laravel\Eloquent\Filter\BooleanFilter;
use ApiPlatform\Metadata\QueryParameter;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\TestCase;

final class BooleanFilterTest extends TestCase
{
    public function testOperator(): void
    {
        $f = new BooleanFilter();
        $builder = $this->createStub(Builder::class);
        $this->assertEquals($builder, $f->apply($builder, ['is_active' => 'true'], new QueryParameter(key: 'isActive', property: 'is_active')));
    }
}
