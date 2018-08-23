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

use ApiPlatform\Core\Identifier\Normalizer\DateTimeIdentifierDenormalizer;
use PHPUnit\Framework\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DateTimeIdentifierNormalizerTest extends TestCase
{
    public function testDenormalize()
    {
        $this->expectException(\ApiPlatform\Core\Exception\InvalidIdentifierException::class);

        $normalizer = new DateTimeIdentifierDenormalizer();
        $normalizer->denormalize('not valid', \DateTimeImmutable::class);
    }

    public function testHasCacheableSupportsMethod()
    {
        $this->assertTrue((new DateTimeIdentifierDenormalizer())->hasCacheableSupportsMethod());
    }
}
