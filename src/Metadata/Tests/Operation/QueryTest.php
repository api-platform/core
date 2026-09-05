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

namespace ApiPlatform\Metadata\Tests\Operation;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Query;
use PHPUnit\Framework\TestCase;

final class QueryTest extends TestCase
{
    public function testItIsASafeCollectionOperation(): void
    {
        $operation = new Query();

        $this->assertInstanceOf(CollectionOperationInterface::class, $operation);
        $this->assertSame(HttpOperation::METHOD_QUERY, $operation->getMethod());
        $this->assertTrue($operation->canRead());
        $this->assertFalse($operation->canWrite());
        $this->assertFalse($operation->canValidate());
        $this->assertFalse($operation->canDeserialize());
    }

    public function testFlagsCanBeOverridden(): void
    {
        $operation = new Query(read: false, write: true, validate: true);

        $this->assertFalse($operation->canRead());
        $this->assertTrue($operation->canWrite());
        $this->assertTrue($operation->canValidate());
    }
}
