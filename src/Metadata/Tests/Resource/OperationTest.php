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

namespace ApiPlatform\Metadata\Tests\Resource;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use PHPUnit\Framework\TestCase;

final class OperationTest extends TestCase
{
    public function testWithResourceTrait(): void
    {
        $operation = (new GetCollection())->withOperation((new HttpOperation())->withShortName('test')->withRead(false));

        $this->assertSame($operation->getShortName(), 'test');
        $this->assertSame($operation->canRead(), false);
        $this->assertSame($operation instanceof CollectionOperationInterface, true);
    }

    /**
     * @dataProvider operationProvider
     */
    public function testOperationConstructor(Operation $operation): void
    {
        $this->assertInstanceOf(Operation::class, $operation);
    }

    public function operationProvider(): \Generator
    {
        $args = [];

        yield [new Get(...$args)];
        yield [new GetCollection(...$args)];
        yield [new Post(...$args)];
        yield [new Put(...$args)];
        yield [new Patch(...$args)];
        yield [new Delete(...$args)];
        yield [new Query(...$args)];
        yield [new QueryCollection(...$args)];
        yield [new Mutation(...$args)];
        yield [new Subscription(...$args)];
    }
}
