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
use ApiPlatform\Core\PathResolver\UnderscoreOperationPathResolver;
use PHPUnit\Framework\TestCase;

/**
 * @author Guilhem N. <egetick@gmail.com>
 *
 * @group legacy
 */
class UnderscoreOperationPathResolverTest extends TestCase
{
    /**
     * @expectedDeprecation The use of ApiPlatform\Core\PathResolver\UnderscoreOperationPathResolver is deprecated since 2.1. Please use ApiPlatform\Core\Operation\UnderscorePathSegmentNameGenerator instead.
     */
    public function testResolveCollectionOperationPath()
    {
        $underscoreOperationPathResolver = new UnderscoreOperationPathResolver();

        $this->assertSame('/short_names.{_format}', $underscoreOperationPathResolver->resolveOperationPath('ShortName', [], OperationType::COLLECTION, 'get'));
    }

    /**
     * @expectedDeprecation The use of ApiPlatform\Core\PathResolver\UnderscoreOperationPathResolver is deprecated since 2.1. Please use ApiPlatform\Core\Operation\UnderscorePathSegmentNameGenerator instead.
     */
    public function testResolveItemOperationPath()
    {
        $underscoreOperationPathResolver = new UnderscoreOperationPathResolver();

        $this->assertSame('/short_names/{id}.{_format}', $underscoreOperationPathResolver->resolveOperationPath('ShortName', [], OperationType::ITEM, 'get'));
    }

    /**
     * @expectedDeprecation The use of ApiPlatform\Core\PathResolver\UnderscoreOperationPathResolver is deprecated since 2.1. Please use ApiPlatform\Core\Operation\UnderscorePathSegmentNameGenerator instead.
     */
    public function testResolveSubresourceOperationPath()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Subresource operations are not supported by the OperationPathResolver.');

        $dashOperationPathResolver = new UnderscoreOperationPathResolver();

        $dashOperationPathResolver->resolveOperationPath('ShortName', ['property' => 'relatedFoo', 'identifiers' => [['id', 'class']], 'collection' => true], OperationType::SUBRESOURCE, 'get');
    }

    /**
     * @expectedDeprecation The use of ApiPlatform\Core\PathResolver\UnderscoreOperationPathResolver is deprecated since 2.1. Please use ApiPlatform\Core\Operation\UnderscorePathSegmentNameGenerator instead.
     * @expectedDeprecation Using a boolean for the Operation Type is deprecated since API Platform 2.1 and will not be possible anymore in API Platform 3
     */
    public function testLegacyResolveOperationPath()
    {
        $underscoreOperationPathResolver = new UnderscoreOperationPathResolver();

        $this->assertSame('/short_names.{_format}', $underscoreOperationPathResolver->resolveOperationPath('ShortName', [], true));
    }
}
