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
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

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
    private $resourceClassResolver;

    public function setUp()
    {
        $dummy1 = new Dummy();
        $dummy1->setName('dummy1');

        $this->halCollection = ['dummy' => $dummy1];
        $contextBuilder = $this->prophesize(ContextBuilderInterface::class);


        $serializer = $this->prophesize(SerializerInterface::class);
        $serializer->willImplement(NormalizerInterface::class);
        $this->resourceClassResolver = $this->prophesize(ResourceClassResolverInterface::class);

        $serializer->normalize($dummy1,
                              'jsonhal',
            ['jsonhal_has_context' => true, 'jsonhal_sub_level' => true, 'resource_class' => 'dummy'])
            ->willReturn(['name' => 'dummy1']);
        $iriConverter = $this->prophesize(IriConverterInterface::class);
        $formats = ['jsonhal' => ['mime_types' => ['application/hal+json']]];
        $iriConverter->getIriFromResourceClass('dummy')->willReturn('/dummies');
        $this->collectionNormalizer = new CollectionNormalizer($contextBuilder->reveal(), $this->resourceClassResolver->reveal(), $iriConverter->reveal(), $formats);
        $this->collectionNormalizer->setSerializer($serializer->reveal());
        $contextBuilder->getBaseContext(0, '/dummies')->willReturn([]);
    }

    public function testSupportsNormalization()
    {
        $this->assertEquals(true, $this->collectionNormalizer->supportsNormalization($this->halCollection, 'jsonhal'));
    }

    public function testNormalize()
    {
        $this->resourceClassResolver->getResourceClass($this->halCollection, null, true)->willReturn('dummy')->shouldBeCalled();

        $expected = [
            '_embedded' => [0 => ['name' => 'dummy1']],
        ];
        $this->assertEquals($expected, $this->collectionNormalizer->normalize($this->halCollection, 'jsonhal'));
    }
}
