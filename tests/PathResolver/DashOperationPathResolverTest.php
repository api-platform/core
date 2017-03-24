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

namespace ApiPlatform\Core\Tests\PathResolver;

use ApiPlatform\Core\PathResolver\DashOperationPathResolver;

class DashOperationPathResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testResolveCollectionOperationPath()
    {
        $dashOperationPathResolver = new DashOperationPathResolver();

        $this->assertSame('/short-names.{_format}', $dashOperationPathResolver->resolveOperationPath('ShortName', [], true));
    }

    public function testResolveItemOperationPath()
    {
        $dashOperationPathResolver = new DashOperationPathResolver();

        $this->assertSame('/short-names/{id}.{_format}', $dashOperationPathResolver->resolveOperationPath('ShortName', [], false));
    }
}
