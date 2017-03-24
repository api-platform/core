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

use ApiPlatform\Core\PathResolver\UnderscoreOperationPathResolver;

class UnderscoreOperationPathResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testResolveCollectionOperationPath()
    {
        $underscoreOperationPathResolver = new UnderscoreOperationPathResolver();

        $this->assertSame('/short_names.{_format}', $underscoreOperationPathResolver->resolveOperationPath('ShortName', [], true));
    }
}
