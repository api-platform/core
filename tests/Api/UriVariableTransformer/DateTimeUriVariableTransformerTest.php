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

namespace ApiPlatform\Tests\Api\UriVariableTransformer;

use ApiPlatform\Api\UriVariableTransformer\DateTimeUriVariableTransformer;
use ApiPlatform\Exception\InvalidUriVariableException;
use PHPUnit\Framework\TestCase;

class DateTimeUriVariableTransformerTest extends TestCase
{
    public function testTransform()
    {
        $this->expectException(InvalidUriVariableException::class);

        $normalizer = new DateTimeUriVariableTransformer();
        $normalizer->transform('not valid', [\DateTimeImmutable::class]);
    }
}
