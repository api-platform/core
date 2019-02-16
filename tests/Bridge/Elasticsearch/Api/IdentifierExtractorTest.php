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

namespace ApiPlatform\Core\Tests\Bridge\Elasticsearch\Api;

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\Api\IdentifierExtractor;
use ApiPlatform\Core\Bridge\Elasticsearch\Api\IdentifierExtractorInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\Exception\NonUniqueIdentifierException;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CompositeRelation;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;

class IdentifierExtractorTest extends TestCase
{
    public function testConstruct()
    {
        self::assertInstanceOf(
            IdentifierExtractorInterface::class,
            new IdentifierExtractor($this->prophesize(IdentifiersExtractorInterface::class)->reveal())
        );
    }

    public function testGetIdentifierFromResourceClass()
    {
        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromResourceClass(Dummy::class)->willReturn(['id'])->shouldBeCalled();

        $identifierExtractor = new IdentifierExtractor($identifiersExtractorProphecy->reveal());

        self::assertSame('id', $identifierExtractor->getIdentifierFromResourceClass(Dummy::class));
    }

    public function testGetIdentifierFromResourceClassWithCompositeIdentifiers()
    {
        $this->expectException(NonUniqueIdentifierException::class);
        $this->expectExceptionMessage('Composite identifiers not supported.');

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromResourceClass(CompositeRelation::class)->willReturn(['compositeItem', 'compositeLabel'])->shouldBeCalled();

        $identifierExtractor = new IdentifierExtractor($identifiersExtractorProphecy->reveal());
        $identifierExtractor->getIdentifierFromResourceClass(CompositeRelation::class);
    }

    public function testGetIdentifierFromResourceClassWithNoIdentifier()
    {
        $this->expectException(NonUniqueIdentifierException::class);
        $this->expectExceptionMessage('Resource "ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy" has no identifiers.');

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromResourceClass(Dummy::class)->willReturn([])->shouldBeCalled();

        $identifierExtractor = new IdentifierExtractor($identifiersExtractorProphecy->reveal());
        $identifierExtractor->getIdentifierFromResourceClass(Dummy::class);
    }
}
