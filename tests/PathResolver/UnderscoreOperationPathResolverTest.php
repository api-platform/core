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
use ApiPlatform\Core\PathResolver\UnderscoreOperationPathResolver;

/**
 * @author Guilhem N. <egetick@gmail.com>
 */
class UnderscoreOperationPathResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testResolveCollectionOperationPath()
    {
        $underscoreOperationPathResolver = new UnderscoreOperationPathResolver();

        $this->assertSame('/short_names.{_format}', $underscoreOperationPathResolver->resolveOperationPath('ShortName', [], OperationType::COLLECTION, 'get'));
    }

    public function testResolveItemOperationPath()
    {
        $underscoreOperationPathResolver = new UnderscoreOperationPathResolver();

        $this->assertSame('/short_names/{id}.{_format}', $underscoreOperationPathResolver->resolveOperationPath('ShortName', [], OperationType::ITEM, 'get'));
    }

    public function testResolveSubresourceOperationPath()
    {
        $dashOperationPathResolver = new UnderscoreOperationPathResolver();

        $path = $dashOperationPathResolver->resolveOperationPath('ShortName', ['property' => 'relatedFoo', 'identifiers' => [['id', 'class']], 'collection' => true], OperationType::SUBRESOURCE, 'get');

        $this->assertSame('/short_names/{id}/related_foos.{_format}', $path);

        $next = $dashOperationPathResolver->resolveOperationPath($path, ['property' => 'bar', 'identifiers' => [['id', 'class'], ['relatedId', 'class']], 'collection' => false], OperationType::SUBRESOURCE, 'get');

        $this->assertSame('/short_names/{id}/related_foos/{relatedId}/bar.{_format}', $next);
    }

    /**
     * @group legacy
     * @expectedDeprecation Method ApiPlatform\Core\PathResolver\UnderscoreOperationPathResolver::resolveOperationPath() will have a 4th `string $operationName` argument in version 3.0. Not defining it is deprecated since 2.1.
     * @expectedDeprecation Using a boolean for the Operation Type is deprecrated since API Platform 2.1 and will not be possible anymore in API Platform 3
     */
    public function testLegacyResolveOperationPath()
    {
        $underscoreOperationPathResolver = new UnderscoreOperationPathResolver();

        $this->assertSame('/short_names.{_format}', $underscoreOperationPathResolver->resolveOperationPath('ShortName', [], true));
    }
}
