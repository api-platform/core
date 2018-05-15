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

namespace ApiPlatform\Core\Tests\Metadata\Resource\Factory;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Metadata\Resource\Factory\AnnotationResourceFilterMetadataFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Doctrine\Orm\Filter\DummyFilter;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\Common\Annotations\Reader;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class AnnotationResourceFilterMetadataFactoryTest extends TestCase
{
    public function testCreate()
    {
        $decorated = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decorated->create(Dummy::class)->willReturn(new ResourceMetadata('hello', 'blabla'))->shouldBeCalled();

        $reader = $this->prophesize(Reader::class);
        $reader->getClassAnnotations(Argument::type(\ReflectionClass::class))->shouldBeCalled()->willReturn([
            new ApiFilter(['value' => DummyFilter::class]),
        ]);

        $reader->getPropertyAnnotations(Argument::type(\ReflectionProperty::class))->shouldBeCalled()->willReturn([]);

        $factory = new AnnotationResourceFilterMetadataFactory($reader->reveal(), $decorated->reveal());

        $metadata = $factory->create(Dummy::class);

        $this->assertEquals(['filters' => [
            'annotated_api_platform_core_tests_fixtures_test_bundle_entity_dummy_api_platform_core_tests_fixtures_test_bundle_doctrine_orm_filter_dummy_filter',
        ]], $metadata->getAttributes());
    }
}
