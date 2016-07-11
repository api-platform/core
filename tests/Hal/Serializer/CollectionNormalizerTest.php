<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Hal\Serializer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Hal\Serializer\CollectionNormalizer;
use ApiPlatform\Core\Hypermedia\ContextBuilderInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Tests\Fixtures\Dummy;


/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class CollectionNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CollectionNormalizer
     */
    private $collectionNormalizer;
    private $halCollection;

    public function setUp()
    {
    $this->halCollection = [
        ['name' => 'dummy1'],
        ['name' => 'dummy2']
    ];
        $contextBuilder = $this->prophesize(ContextBuilderInterface::class);

        $resourceClassResolver = $this->prophesize(ResourceClassResolverInterface::class);
        $serializer = $this->prophesize(SerializerInterface::class);
        $iriConverter = $this->prophesize(IriConverterInterface::class);
        $formats = ['jsonhal' => ['mime_types' => ['application/hal+json']]];
        $this->collectionNormalizer = new CollectionNormalizer($contextBuilder->reveal(), $resourceClassResolver->reveal(), $iriConverter->reveal(), $formats);
        $contextBuilder->getBaseContext(0, '/dummies')->willReturn('/dummies');
        $resourceClassResolver->getResourceClass($this->halCollection , null, true)->willReturn('dummy');
        $this->collectionNormalizer->setSerializer($serializer->reveal());
    }

    public function testSupportsNormalization() {
        $this->assertEquals(true, $this->collectionNormalizer->supportsNormalization($this->halCollection, 'jsonhal'));
    }

    public function testNormalize() {
        $expected = [
            '_links' => ['self' => ['href' => '/dummies'],
                         'curies' => [
                             ['name' => 'ap',
                              'href' => '/doc#section-{rel}',
                              'templated' => true,
                             ],
                         ],
            ],
            '_embedded' => ['_links' => ['self' => ['href' => '/dummies/1']]],
        ];
        $this->assertEquals($expected, $this->collectionNormalizer->normalize($this->halCollection, 'jsonhal'));
    }

}
