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

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Operation\UnderscorePathSegmentNameGenerator;
use ApiPlatform\Core\PathResolver\OperationPathResolver;
use PHPUnit\Framework\TestCase;

class OperationPathResolverTest extends TestCase
{
    public function testResolveItemOperationPath()
    {
        $operationPathResolver = new OperationPathResolver(new UnderscorePathSegmentNameGenerator());
        $this->assertEquals('/foos/{id}.{_format}', $operationPathResolver->resolveOperationPath('Foo', [], OperationType::ITEM, 'get'));
    }

    public function testResolveItemOperationPathIdentifiedBy()
    {
        $operationPathResolver = new OperationPathResolver(new UnderscorePathSegmentNameGenerator());
        $this->assertSame('/short_names/{isbn}.{_format}', $operationPathResolver->resolveOperationPath('ShortName', ['identifiers' => ['isbn']], OperationType::ITEM, 'get'));
    }
}
