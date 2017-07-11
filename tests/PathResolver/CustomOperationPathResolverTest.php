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

namespace ApiPlatform\Core\Tests\PathResolver;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\PathResolver\CustomOperationPathResolver;
use ApiPlatform\Core\PathResolver\OperationPathResolverInterface;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class CustomOperationPathResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testResolveOperationPath()
    {
        $operationPathResolver = new CustomOperationPathResolver($this->prophesize(OperationPathResolverInterface::class)->reveal());

        $this->assertEquals('/foos.{_format}', $operationPathResolver->resolveOperationPath('Foo', ['path' => '/foos.{_format}'], OperationType::COLLECTION, 'get'));
    }

    public function testResolveOperationPathWithDeferred()
    {
        $operationPathResolverProphecy = $this->prophesize(OperationPathResolverInterface::class);
        $operationPathResolverProphecy->resolveOperationPath('Foo', [], OperationType::ITEM, 'get')->willReturn('/foos/{id}.{_format}')->shouldBeCalled();

        $operationPathResolver = new CustomOperationPathResolver($operationPathResolverProphecy->reveal());

        $this->assertEquals('/foos/{id}.{_format}', $operationPathResolver->resolveOperationPath('Foo', [], OperationType::ITEM, 'get'));
    }

    /**
     * @group legacy
     * @expectedDeprecation Using a boolean for the Operation Type is deprecrated since API Platform 2.1 and will not be possible anymore in API Platform 3
     */
    public function testLegacyResolveOperationPath()
    {
        $operationPathResolver = new CustomOperationPathResolver($this->prophesize(OperationPathResolverInterface::class)->reveal());

        $this->assertEquals('/foos.{_format}', $operationPathResolver->resolveOperationPath('Foo', ['path' => '/foos.{_format}'], true));
    }
}
