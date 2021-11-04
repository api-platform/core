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

namespace ApiPlatform\Core\Tests\Bridge\Elasticsearch\Metadata\Document;

use ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\DocumentMetadata;
use PHPUnit\Framework\TestCase;

class DocumentMetadataTest extends TestCase
{
    public function testValueObject()
    {
        $documentMetadataOne = new DocumentMetadata('foo', 'bar');

        self::assertSame('foo', $documentMetadataOne->getIndex());
        self::assertSame('bar', $documentMetadataOne->getType());

        $documentMetadataTwo = $documentMetadataOne->withIndex('baz');

        self::assertNotSame($documentMetadataTwo, $documentMetadataOne);
        self::assertSame('baz', $documentMetadataTwo->getIndex());
        self::assertSame('bar', $documentMetadataTwo->getType());

        $documentMetadataThree = $documentMetadataTwo->withType(DocumentMetadata::DEFAULT_TYPE);

        self::assertNotSame($documentMetadataThree, $documentMetadataOne);
        self::assertNotSame($documentMetadataThree, $documentMetadataTwo);
        self::assertSame('baz', $documentMetadataThree->getIndex());
        self::assertSame(DocumentMetadata::DEFAULT_TYPE, $documentMetadataThree->getType());
    }
}
