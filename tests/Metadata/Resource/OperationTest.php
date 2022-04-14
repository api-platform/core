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

namespace ApiPlatform\Tests\Metadata\Resource;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HttpOperation;
use PHPUnit\Framework\TestCase;

final class OperationTest extends TestCase
{
    public function testWithResourceTrait()
    {
        $operation = (new GetCollection())->withOperation((new HttpOperation())->withShortName('test')->withRead(false));

        $this->assertEquals($operation->getShortName(), 'test');
        $this->assertEquals($operation->canRead(), false);
        $this->assertEquals($operation instanceof CollectionOperationInterface, true);
    }
}
