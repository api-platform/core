<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\Dunglas\JsonLdApiBundle\Serializer;

use Dunglas\JsonLdApiBundle\Mapping\ClassMetadata;
use Dunglas\JsonLdApiBundle\Mapping\ClassMetadataFactory;
use Dunglas\JsonLdApiBundle\Model\DataProviderInterface;
use Dunglas\JsonLdApiBundle\JsonLd\Resource;
use Dunglas\JsonLdApiBundle\JsonLd\Resources;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Routing\RouterInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class JsonLdNormalizerSpec extends ObjectBehavior
{
    public function let(
        Resources $resources,
        RouterInterface $router,
        DataProviderInterface $dataManipulator,
        ClassMetadataFactory $classMetadataFactory,
        ClassMetadata $classMetadata)
    {
        $this->beConstructedWith($resources, $router, $dataManipulator, $classMetadataFactory);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Dunglas\JsonLdApiBundle\Serializer\JsonLdNormalizer');
        $this->shouldImplement('Symfony\Component\Serializer\Normalizer\NormalizerInterface');
        $this->shouldImplement('Symfony\Component\Serializer\Normalizer\DenormalizerInterface');
    }

    /*function it_ignores_non_existing_attributes(Resource $resource)
    {
        $this
            ->denormalize(array('nonExisting' => true), __NAMESPACE__.'\Dummy', null, array('resource' => $resource))
            ->willReturn(new Dummy())
        ;
    }*/
}

class Dummy
{
}
