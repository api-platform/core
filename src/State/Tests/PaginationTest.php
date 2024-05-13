<?php

declare(strict_types=1);

namespace ApiPlatform\State\Tests;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\State\Pagination\Pagination;
use PHPUnit\Framework\TestCase;

class PaginationTest extends TestCase
{
    public function testPaginationGetPaginationWithDefaultOptionsAndDefaultContext(): void
    {
        $operation = new GetCollection(name: 'hello', provider: 'provider');
        $pagination = new Pagination();
        $paginationInfo = $pagination->getPagination($operation);
        $this->assertSame(1, $paginationInfo[0]);
        $this->assertSame(0, $paginationInfo[1]);
        $this->assertSame(30, $paginationInfo[2]);
    }
    public function testPaginationGetPaginationWithPageParameterNameAsArrayAndDefaultContext(): void
    {
        $operation = new GetCollection(name: 'hello', provider: 'provider');
        $pagination = new Pagination(['page_parameter_name' => 'page[number]']);
        $paginationInfo = $pagination->getPagination($operation);
        $this->assertSame(1, $paginationInfo[0]);
        $this->assertSame(0, $paginationInfo[1]);
        $this->assertSame(30, $paginationInfo[2]);
    }
    public function testPaginationGetPaginationWithPageParametersAsArrayAndCustomContext(): void
    {
        $operation = new GetCollection(paginationClientItemsPerPage: true, name: 'hello', provider: 'provider');
        $pagination = new Pagination([
            'page_parameter_name' => 'page[number]',
            'items_per_page_parameter_name' => 'page[size]',
        ]);
        $paginationInfo = $pagination->getPagination(
            $operation,
            [
                'filters' => [
                    'number' => 2,
                    'size' => 10,
                    '_page' => [
                        'number' => 2,
                        'size' => 10,
                    ],
                ],
            ],
        );
        $this->assertSame(2, $paginationInfo[0]);
        $this->assertSame(10, $paginationInfo[1]);
        $this->assertSame(10, $paginationInfo[2]);
    }
}
