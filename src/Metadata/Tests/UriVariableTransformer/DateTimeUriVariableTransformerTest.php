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

use ApiPlatform\Metadata\Exception\InvalidUriVariableException;
use ApiPlatform\Metadata\UriVariableTransformer\DateTimeUriVariableTransformer;
use PHPUnit\Framework\TestCase;

class DateTimeUriVariableTransformerTest extends TestCase
{
    public function testTransform(): void
    {
        $this->expectException(InvalidUriVariableException::class);

        $normalizer = new DateTimeUriVariableTransformer();
        $normalizer->transform('not valid', [\DateTimeImmutable::class]);
    }

    public function testSupportsTransformationForDateTime(): void
    {
        $normalizer = new DateTimeUriVariableTransformer();

        $this->assertTrue($normalizer->supportsTransformation(1, [\DateTime::class]));
        $this->assertTrue($normalizer->supportsTransformation(2, [\DateTimeImmutable::class]));
        $this->assertTrue($normalizer->supportsTransformation(3, [\DateTimeInterface::class]));
    }
}
