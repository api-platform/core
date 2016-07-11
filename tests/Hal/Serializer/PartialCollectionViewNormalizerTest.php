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

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\Hal\ContextBuilder;
use ApiPlatform\Core\Hal\Serializer\CollectionNormalizer;
use ApiPlatform\Core\Hal\Serializer\PartialCollectionViewNormalizer;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class PartialCollectionViewNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PartialCollectionViewNormalizer
     */
    private $partialCollectionView;
    /**
     * @var CollectionNormalizer
     */
    private $collectionNormalizer;

    public function setUp()
    {
       $this->collectionNormalizer = $this->prophesize(NormalizerInterface::class);
       $pageParameterName = 'page';
       $enableParameterName = 'pagination_enabled';
       $formats = ['jsonhal' => ['mime_types' => ['application/hal+json']]];
       $this->partialCollectionView = new PartialCollectionViewNormalizer($this->collectionNormalizer->reveal(), $pageParameterName, $enableParameterName, $formats);

    }
    public function testNormalize()
    {

        $paginatorInteface = $this->prophesize(PaginatorInterface::class);

        $paginatorInteface->getCurrentPage()->willReturn(1);
        $paginatorInteface->getLastPage()->willReturn(2);
        $paginatorInteface->reveal();
        $this->collectionNormalizer->normalize($paginatorInteface, 'jsonhal', [])->shouldBeCalled();
        $halCollection = [
            '_links' => ['self' => ['href' => '/dummies'],
                         'curies' => [
                             ['name' => 'ap',
                              'href' => '/doc#section-{rel}',
                              'templated' => true,
                             ],
                         ],
            ],
            '_embedded' => ['_links' => ['self' => ['href' => '/dummies/1']]],
            'name' => 'dummy'
        ];
        $this->collectionNormalizer->normalize($paginatorInteface, 'jsonhal', [])->willReturn($halCollection);

        $halCollection['_links']['self']['next'] = '/?page=2';

        $this->assertEquals($halCollection, $this->partialCollectionView->normalize($paginatorInteface->reveal(),'jsonhal', []));
    }
}
