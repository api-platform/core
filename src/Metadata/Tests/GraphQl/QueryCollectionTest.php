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

namespace ApiPlatform\Metadata\Tests\GraphQl;

use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\State\OptionsInterface;
use PHPUnit\Framework\TestCase;

final class QueryCollectionTest extends TestCase
{
    public function testStateOptions(): void
    {
        $stateOptions = new class implements OptionsInterface {
        };

        $this->assertSame($stateOptions, (new QueryCollection(stateOptions: $stateOptions))->getStateOptions());
    }

    public function testMap(): void
    {
        $this->assertTrue((new QueryCollection(map: true))->canMap());
    }
}
