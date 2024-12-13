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

namespace ApiPlatform\Metadata\Tests;

use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\State\ParameterNotFound;
use PHPUnit\Framework\TestCase;

class ParameterTest extends TestCase
{
    public function testDefaultValue(): void
    {
        $parameter = new QueryParameter();
        $this->assertSame('test', $parameter->getValue('test'));
        $this->assertInstanceOf(ParameterNotFound::class, $parameter->getValue());
    }
}
