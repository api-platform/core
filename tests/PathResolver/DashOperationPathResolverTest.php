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

/**
 * @author Guilhem N. <egetick@gmail.com>
 */
class DashOperationPathResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testResolveCollectionOperationPath()
    {
        $dashOperationPathResolver = new DashOperationPathResolver();

        $this->assertSame('/short-names.{_format}', $dashOperationPathResolver->resolveOperationPath('ShortName', [], OperationType::COLLECTION, 'get'));
    }

    public function testResolveItemOperationPath()
    {
        $dashOperationPathResolver = new DashOperationPathResolver();

        $this->assertSame('/short-names/{id}.{_format}', $dashOperationPathResolver->resolveOperationPath('ShortName', [], OperationType::ITEM, 'get'));
    }

    public function testResolveSubresourceOperationPath()
    {
        $dashOperationPathResolver = new DashOperationPathResolver();

        $path = $dashOperationPathResolver->resolveOperationPath('ShortName', ['property' => 'relatedFoo', 'identifiers' => [['id', 'class']], 'collection' => true], OperationType::SUBRESOURCE, 'get');

        $this->assertSame('/short-names/{id}/related-foos.{_format}', $path);

        $next = $dashOperationPathResolver->resolveOperationPath($path, ['property' => 'bar', 'identifiers' => [['id', 'class'], ['relatedId', 'class']], 'collection' => false], OperationType::SUBRESOURCE, 'get');

        $this->assertSame('/short-names/{id}/related-foos/{relatedId}/bar.{_format}', $next);
    }

    /**
     * @group legacy
     * @expectedDeprecation Method ApiPlatform\Core\PathResolver\DashOperationPathResolver::resolveOperationPath() will have a 4th `string $operationName` argument in version 3.0. Not defining it is deprecated since 2.1.
     * @expectedDeprecation Using a boolean for the Operation Type is deprecrated since API Platform 2.1 and will not be possible anymore in API Platform 3
     */
    public function testLegacyResolveOperationPath()
    {
        $dashOperationPathResolver = new DashOperationPathResolver();

        $this->assertSame('/short-names.{_format}', $dashOperationPathResolver->resolveOperationPath('ShortName', [], true));
    }
}
