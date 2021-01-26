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

namespace ApiPlatform\Core\Tests\Bridge\Elasticsearch\Serializer;

use ApiPlatform\Core\Bridge\Elasticsearch\Api\IdentifierExtractorInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\Serializer\DocumentNormalizer;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Foo;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class DocumentNormalizerTest extends TestCase
{
    use ProphecyTrait;

    public function testConstruct()
    {
        $itemNormalizer = new DocumentNormalizer($this->prophesize(IdentifierExtractorInterface::class)->reveal());

        self::assertInstanceOf(DenormalizerInterface::class, $itemNormalizer);
        self::assertInstanceOf(NormalizerInterface::class, $itemNormalizer);
    }

    public function testSupportsDenormalization()
    {
        $document = [
            '_index' => 'test',
            '_type' => '_doc',
            '_id' => '1',
            '_version' => 1,
            'found' => true,
            '_source' => [
                'id' => 1,
                'name' => 'Caroline',
                'bar' => 'Chaverot',
            ],
        ];

        $itemNormalizer = new DocumentNormalizer($this->prophesize(IdentifierExtractorInterface::class)->reveal());

        self::assertTrue($itemNormalizer->supportsDenormalization($document, Foo::class, DocumentNormalizer::FORMAT));
        self::assertFalse($itemNormalizer->supportsDenormalization($document, Foo::class, 'text/coffee'));
    }

    public function testDenormalize()
    {
        $document = [
            '_index' => 'test',
            '_type' => '_doc',
            '_id' => '1',
            '_version' => 1,
            'found' => true,
            '_source' => [
                'name' => 'Caroline',
                'bar' => 'Chaverot',
            ],
        ];

        $identifierExtractorProphecy = $this->prophesize(IdentifierExtractorInterface::class);
        $identifierExtractorProphecy->getIdentifierFromResourceClass(Foo::class)->willReturn('id')->shouldBeCalled();

        $normalizer = new DocumentNormalizer($identifierExtractorProphecy->reveal());

        $expectedFoo = new Foo();
        $expectedFoo->setName('Caroline');
        $expectedFoo->setBar('Chaverot');

        self::assertEquals($expectedFoo, $normalizer->denormalize($document, Foo::class, DocumentNormalizer::FORMAT));
    }

    public function testSupportsNormalization()
    {
        $itemNormalizer = new DocumentNormalizer($this->prophesize(IdentifierExtractorInterface::class)->reveal());

        self::assertTrue($itemNormalizer->supportsNormalization(new Foo(), DocumentNormalizer::FORMAT));
    }

    public function testNormalize()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('%s is a write-only format.', DocumentNormalizer::FORMAT));

        (new DocumentNormalizer($this->prophesize(IdentifierExtractorInterface::class)->reveal()))->normalize(new Foo(), DocumentNormalizer::FORMAT);
    }
}
