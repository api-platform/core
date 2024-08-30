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

namespace ApiPlatform\Metadata\Tests\Util;

use ApiPlatform\Metadata\Util\SortTrait;
use PHPUnit\Framework\TestCase;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class SortTraitTest extends TestCase
{
    private function getSortTraitImplementation(): object
    {
        return new class {
            use SortTrait {
                SortTrait::arrayRecursiveSort as public;
            }
        };
    }

    public function testArrayRecursiveSort(): void
    {
        $sortTrait = $this->getSortTraitImplementation();

        $array = [
            'second',
            [
                'second',
                'first',
            ],
            'first',
        ];

        $sortTrait->arrayRecursiveSort($array, 'sort');

        $this->assertEquals([
            'first',
            'second',
            [
                'first',
                'second',
            ],
        ], $array);
    }
}
