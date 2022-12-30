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

namespace ApiPlatform\Tests\Elasticsearch\State;

use ApiPlatform\Elasticsearch\Metadata\ElasticsearchDocument;
use ApiPlatform\Elasticsearch\State\ElasticsearchItemProvider;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Foo;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class ElasticsearchItemProviderTest extends TestCase
{
    public function testConstruct(): void
    {
        self::assertInstanceOf(
            ElasticsearchItemProvider::class,
            new ElasticsearchItemProvider(
                $this->createStub(Client::class),
                $this->createStub(DenormalizerInterface::class)
            )
        );
    }

    public function testGetItem(): void
    {
        $document = [
            '_index' => 'foo',
            '_type' => '_doc',
            '_id' => '1',
            '_version' => 1,
            'found' => true,
            '_source' => [
                'id' => 1,
                'name' => 'Rossinière',
                'bar' => 'erèinissor',
            ],
        ];

        $foo = new Foo();
        $foo->setName('Rossinière');
        $foo->setBar('erèinissor');

        $client = $this->createStub(Client::class);
        $client->method('get')->willReturn($document);

        $denormalizer = $this->createStub(DenormalizerInterface::class);
        $denormalizer->method('denormalize')->willReturn($foo);

        $itemDataProvider = new ElasticsearchItemProvider($client, $denormalizer);

        self::assertSame($foo, $itemDataProvider->provide(new Get(class: Foo::class, persistenceMeans: new ElasticsearchDocument('foo')), ['id' => 1]));
    }

    public function testGetItemWithMissing404Exception(): void
    {
        $client = $this->createStub(Client::class);
        $client->method('get')->willThrowException(new Missing404Exception());

        $itemDataProvider = new ElasticsearchItemProvider($client, $this->createStub(DenormalizerInterface::class));

        self::assertNull($itemDataProvider->provide(new Get(class: Foo::class, persistenceMeans: new ElasticsearchDocument('foo')), ['id' => 404]));
    }
}
