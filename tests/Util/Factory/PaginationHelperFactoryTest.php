<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\tests\Util\Factory;

use ApiPlatform\Core\Util\Factory\PaginationHelperFactory;
use ApiPlatform\Core\Util\PaginationHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;

/**
 * @author Jonathan Doelfs <jd@sodatech.com>
 */
class PaginationHelperFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testClassIsCorrect()
    {
        $requestStackProphecy = $this->prophesize(RequestStack::class);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);

        $factory = new PaginationHelperFactory($requestStackProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal());
        $this->assertInstanceOf(PaginationHelper::class, $factory->create('someResourceClass', 'someOperationName'));
    }
}
