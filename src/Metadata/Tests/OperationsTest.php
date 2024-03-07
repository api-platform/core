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

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operations;
use PHPUnit\Framework\TestCase;

final class OperationsTest extends TestCase
{
    public function testOperationsHaveNameIfNotSet(): void
    {
        $operations = new Operations([new Get(name: 'a'), new Get(name: 'b')]);

        foreach ($operations as $name => $operation) {
            $this->assertEquals($name, $operation->getName());
        }
    }

    public function testOperationAreSorted(): void
    {
        $operations = new Operations(['a' => new Get(priority: 0), 'b' => new Get(priority: -1)]);
        $this->assertEquals(['b', 'a'], array_keys(iterator_to_array($operations)));
    }
}
