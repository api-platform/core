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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Routing;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Bridge\Symfony\Routing\RouteNameGenerator;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class RouteNameGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $this->assertEquals('api_foos_get_collection', RouteNameGenerator::generate('get', 'Foo', OperationType::COLLECTION));
        $this->assertEquals('api_bars_custom_operation_item', RouteNameGenerator::generate('custom_operation', 'Bar', OperationType::ITEM));
    }

    /**
     * @group legacy
     * @expectedDeprecation Using a boolean for the Operation Type is deprecated since API Platform 2.1 and will not be possible anymore in API Platform 3
     */
    public function testLegacyGenerate()
    {
        $this->assertEquals('api_foos_get_collection', RouteNameGenerator::generate('get', 'Foo', true));
    }

    public function testGenerateWithSubresource()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Subresource operations are not supported by the RouteNameGenerator.');

        $this->assertEquals('api_foos_bar_get_subresource', RouteNameGenerator::generate('get', 'Foo', OperationType::SUBRESOURCE));
    }
}
