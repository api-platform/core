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

namespace ApiPlatform\Core\Tests\Bridge\Elasticsearch\Exception;

use ApiPlatform\Core\Bridge\Elasticsearch\Exception\IndexNotFoundException;
use ApiPlatform\Core\Exception\ExceptionInterface;
use PHPUnit\Framework\TestCase;

class IndexNotFoundExceptionTest extends TestCase
{
    public function testConstruct()
    {
        $indexNotFoundException = new IndexNotFoundException();

        self::assertInstanceOf(ExceptionInterface::class, $indexNotFoundException);
        self::assertInstanceOf(\Throwable::class, $indexNotFoundException);
    }
}
