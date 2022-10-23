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

namespace ApiPlatform\Tests\Metadata\Extractor;

use ApiPlatform\Metadata\Extractor\DynamicResourceExtractor;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;

final class DynamicResourceExtractorTest extends TestCase
{
    public function testAddResource(): void
    {
        $dynamicResourceExtractor = new DynamicResourceExtractor();

        $dynamicResourceName = $dynamicResourceExtractor->addResource(Dummy::class, ['description' => 'A description.']);

        self::assertSame(ResourceMetadataCollection::DYNAMIC_RESOURCE_CLASS_PREFIX.Dummy::class, $dynamicResourceName);
        self::assertSame([
            $dynamicResourceName => [[
                'class' => Dummy::class,
                'description' => 'A description.',
            ]],
        ], $dynamicResourceExtractor->getResources());
    }
}
