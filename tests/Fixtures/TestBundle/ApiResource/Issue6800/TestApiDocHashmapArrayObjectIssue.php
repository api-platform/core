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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6800;

use ApiPlatform\Metadata\Get;

#[Get]
class TestApiDocHashmapArrayObjectIssue
{
    /** @var array<Foo> */
    public array $foos;

    /** @var Foo[] */
    public array $fooOtherSyntax;

    /** @var array<string, Foo> */
    public array $fooHashmaps;
}
