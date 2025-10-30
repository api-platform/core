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

use ApiPlatform\Metadata\HeaderParameter;
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

    public function testDuplicated(): void
    {
        $r1 = new QueryParameter(key: 'a');
        $r2 = new QueryParameter(key: 'b');
        $r3 = new HeaderParameter(key: 'a');
        $r4 = new HeaderParameter(key: 'b');
        $parameters = new Parameters([$r1, $r2, $r3, $r4]);
        $this->assertCount(4, $parameters);
        $this->assertSame($r1, $parameters->get('a'));
        $this->assertSame($r2, $parameters->get('b'));
        $this->assertSame($r3, $parameters->get('a', HeaderParameter::class));
        $this->assertSame($r4, $parameters->get('b', HeaderParameter::class));

        $r1 = new QueryParameter(key: 'a');
        $r2 = new QueryParameter(key: 'a');
        $r3 = new HeaderParameter(key: 'a');
        $r4 = new HeaderParameter(key: 'a');
        $parameters = new Parameters([$r1, $r2, $r3, $r4]);
        $this->assertCount(2, $parameters);
        $this->assertSame($r2, $parameters->get('a'));
        $this->assertSame($r4, $parameters->get('a', HeaderParameter::class));
    }
}
