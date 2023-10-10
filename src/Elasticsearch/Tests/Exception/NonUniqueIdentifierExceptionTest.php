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

namespace ApiPlatform\Elasticsearch\Tests\Exception;

use ApiPlatform\Elasticsearch\Exception\NonUniqueIdentifierException;
use PHPUnit\Framework\TestCase;

class NonUniqueIdentifierExceptionTest extends TestCase
{
    public function testConstruct(): void
    {
        $nonUniqueIdentifierException = new NonUniqueIdentifierException();

        self::assertInstanceOf(NonUniqueIdentifierException::class, $nonUniqueIdentifierException);
        self::assertInstanceOf(\Throwable::class, $nonUniqueIdentifierException);
    }
}
