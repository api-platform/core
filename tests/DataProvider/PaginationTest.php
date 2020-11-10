<?php


namespace ApiPlatform\Core\Tests\DataProvider;

use ApiPlatform\Core\DataProvider\Pagination;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;

/**
 * @author Arnaud POINTET <arnaud.pointet@gmail.com>
 */
class PaginationTest extends TestCase
{
    use ProphecyTrait;

    public function testPaginationParameterNameIsAString()
    {
        $resourceMetadataProphesize = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $options = [
            'page_parameter_name' => 'test_page_parameter'
        ];

        $pagination = new Pagination($resourceMetadataProphesize->reveal(), $options);
        $result = $pagination->getPagination(null, null, [
            'filters' => [
                'test_page_parameter' => 50
            ]
        ]);

        $this->assertEquals(50, $result[0]);
    }

    public function testPaginationParameterNameIsAnArray()
    {
        $resourceMetadataProphesize = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $options = [
            'page_parameter_name' => 'pagination.test_page_parameter'
        ];

        $pagination = new Pagination($resourceMetadataProphesize->reveal(), $options);
        $result = $pagination->getPagination(null, null, [
            'filters' => [
                'pagination' => [
                    'test_page_parameter' => 50
                ]
            ]
        ]);

        $this->assertEquals(50, $result[0]);
    }
}
