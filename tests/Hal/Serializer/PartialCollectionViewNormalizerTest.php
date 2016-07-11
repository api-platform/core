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

use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\Hal\Serializer\CollectionNormalizer;
use ApiPlatform\Core\Hal\Serializer\PartialCollectionViewNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

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
