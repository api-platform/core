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
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\PathResolver\DashOperationPathResolver;
use PHPUnit\Framework\TestCase;

/**
 * @author Guilhem N. <egetick@gmail.com>
 *
 * @group legacy
 */
class DashOperationPathResolverTest extends TestCase
{
    /**
     * @expectedDeprecation The use of ApiPlatform\Core\PathResolver\DashOperationPathResolver is deprecated since 2.1. Please use ApiPlatform\Core\Operation\DashPathSegmentNameGenerator instead.
     */
    public function testResolveCollectionOperationPath()
    {
        $dashOperationPathResolver = new DashOperationPathResolver();

        $this->assertSame('/short-names.{_format}', $dashOperationPathResolver->resolveOperationPath('ShortName', [], OperationType::COLLECTION, 'get'));
    }

    /**
     * @expectedDeprecation The use of ApiPlatform\Core\PathResolver\DashOperationPathResolver is deprecated since 2.1. Please use ApiPlatform\Core\Operation\DashPathSegmentNameGenerator instead.
     */
    public function testResolveItemOperationPath()
    {
        $dashOperationPathResolver = new DashOperationPathResolver();

        $this->assertSame('/short-names/{id}.{_format}', $dashOperationPathResolver->resolveOperationPath('ShortName', [], OperationType::ITEM, 'get'));
    }

    /**
     * @expectedDeprecation The use of ApiPlatform\Core\PathResolver\DashOperationPathResolver is deprecated since 2.1. Please use ApiPlatform\Core\Operation\DashPathSegmentNameGenerator instead.
     */
    public function testResolveSubresourceOperationPath()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Subresource operations are not supported by the OperationPathResolver.');

        $dashOperationPathResolver = new DashOperationPathResolver();

        $dashOperationPathResolver->resolveOperationPath('ShortName', ['property' => 'bar', 'identifiers' => [['id', 'class'], ['relatedId', 'class']], 'collection' => false], OperationType::SUBRESOURCE, 'get');
    }

    /**
     * @expectedDeprecation The use of ApiPlatform\Core\PathResolver\DashOperationPathResolver is deprecated since 2.1. Please use ApiPlatform\Core\Operation\DashPathSegmentNameGenerator instead.
     * @expectedDeprecation Using a boolean for the Operation Type is deprecated since API Platform 2.1 and will not be possible anymore in API Platform 3
     */
    public function testLegacyResolveOperationPath()
    {
        $dashOperationPathResolver = new DashOperationPathResolver();

        $this->assertSame('/short-names.{_format}', $dashOperationPathResolver->resolveOperationPath('ShortName', [], true));
    }
}
