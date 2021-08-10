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

namespace ApiPlatform\Core\Tests\Identifier\Normalizer;

use ApiPlatform\Core\Identifier\Normalizer\IntegerDenormalizer;
use PHPUnit\Framework\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class IntegerDenormalizerTest extends TestCase
{
    public function testDenormalize()
    {
        $this->assertSame(2, (new IntegerDenormalizer())->denormalize('2', 'int'));
    }

    public function testSupportsDenormalization()
    {
        $normalizer = new IntegerDenormalizer();
        $this->assertTrue($normalizer->supportsDenormalization('1', 'int'));
        $this->assertFalse($normalizer->supportsDenormalization([], 'int'));
        $this->assertFalse($normalizer->supportsDenormalization('1', 'foo'));
        $this->assertTrue($normalizer->hasCacheableSupportsMethod());
    }
}
