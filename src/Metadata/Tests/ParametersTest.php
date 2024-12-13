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

use ApiPlatform\Metadata\Parameters;
use ApiPlatform\Metadata\QueryParameter;
use PHPUnit\Framework\TestCase;

class ParametersTest extends TestCase
{
    public function testDefaultValue(): void
    {
        $r = new QueryParameter();
        $parameters = new Parameters(['a' => $r]);
        $this->assertSame($r, $parameters->get('a'));
    }
}
