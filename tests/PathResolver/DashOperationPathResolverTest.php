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
use ApiPlatform\Core\PathResolver\DashOperationPathResolver;

class DashOperationPathResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testResolveCollectionOperationPath()
    {
        $dashOperationPathResolver = new DashOperationPathResolver();

        $this->assertSame('/short-names.{_format}', $dashOperationPathResolver->resolveOperationPath('ShortName', [], OperationType::COLLECTION));
    }

    public function testResolveItemOperationPath()
    {
        $dashOperationPathResolver = new DashOperationPathResolver();

        $this->assertSame('/short-names/{id}.{_format}', $dashOperationPathResolver->resolveOperationPath('ShortName', [], OperationType::ITEM));
    }

    public function testResolveSubresourceOperationPath()
    {
        $dashOperationPathResolver = new DashOperationPathResolver();

        $path = $dashOperationPathResolver->resolveOperationPath('ShortName', ['property' => 'relatedFoo', 'identifiers' => [['id', 'class']], 'collection' => true], OperationType::SUBRESOURCE);

        $this->assertSame('/short-names/{id}/related-foos.{_format}', $path);

        $next = $dashOperationPathResolver->resolveOperationPath($path, ['property' => 'bar', 'identifiers' => [['id', 'class'], ['relatedId', 'class']], 'collection' => false], OperationType::SUBRESOURCE);

        $this->assertSame('/short-names/{id}/related-foos/{relatedId}/bar.{_format}', $next);
    }
}
