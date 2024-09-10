<?php

namespace ApiPlatform\Laravel\Tests\Eloquent\Filter;
use ApiPlatform\Laravel\Eloquent\Filter\DateFilter;
use ApiPlatform\Metadata\QueryParameter;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\TestCase;

class DateFilterTest extends TestCase
{
    public function testOperator()
    {
        $f = new DateFilter();
        $builder = $this->createStub(Builder::class);
        $f->apply($builder,['neq' => '2020-02-02'], new QueryParameter(key: 'date', property: 'date'));
    }
}
