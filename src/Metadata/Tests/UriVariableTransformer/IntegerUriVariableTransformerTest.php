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

namespace ApiPlatform\Metadata\Tests\UriVariableTransformer;

use ApiPlatform\Metadata\UriVariableTransformer\IntegerUriVariableTransformer;
use PHPUnit\Framework\TestCase;

class IntegerUriVariableTransformerTest extends TestCase
{
    public function testTransform(): void
    {
        $this->assertSame(2, (new IntegerUriVariableTransformer())->transform('2', ['int']));
    }

    public function testSupportsTransformation(): void
    {
        $normalizer = new IntegerUriVariableTransformer();
        $this->assertTrue($normalizer->supportsTransformation('1', ['int']));
        $this->assertFalse($normalizer->supportsTransformation([], ['int']));
        $this->assertFalse($normalizer->supportsTransformation('1', ['foo']));
    }
}
