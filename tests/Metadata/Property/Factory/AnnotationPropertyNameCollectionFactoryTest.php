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

namespace ApiPlatform\Core\Tests\Metadata\Property\Factory;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Property\Factory\AnnotationPropertyNameCollectionFactory;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\Common\Annotations\Reader;
use Prophecy\Argument;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AnnotationPropertyNameCollectionFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getDependencies
     */
    public function testCreate(PropertyNameCollectionFactoryInterface $decorated = null, array $results)
    {
        $reader = $this->prophesize(Reader::class);
        $reader->getPropertyAnnotation(new \ReflectionProperty(Dummy::class, 'name'), ApiProperty::class)->willReturn(new ApiProperty())->shouldBeCalled();
        $reader->getPropertyAnnotation(Argument::type(\ReflectionProperty::class), ApiProperty::class)->willReturn(null)->shouldBeCalled();
        $reader->getMethodAnnotation(new \ReflectionMethod(Dummy::class, 'getName'), ApiProperty::class)->willReturn(new ApiProperty())->shouldBeCalled();
        $reader->getMethodAnnotation(new \ReflectionMethod(Dummy::class, 'getAlias'), ApiProperty::class)->willReturn(new ApiProperty())->shouldBeCalled();
        $reader->getMethodAnnotation(new \ReflectionMethod(Dummy::class, 'staticMethod'), ApiProperty::class)->shouldNotBeCalled();
        $reader->getMethodAnnotation(Argument::type(\ReflectionMethod::class), ApiProperty::class)->willReturn(null)->shouldBeCalled();

        $factory = new AnnotationPropertyNameCollectionFactory($reader->reveal(), $decorated);
        $metadata = $factory->create(Dummy::class, []);

        $this->assertEquals($results, iterator_to_array($metadata));
    }

    public function getDependencies()
    {
        $decoratedThrowsNotFound = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $decoratedThrowsNotFound->create(Dummy::class, [])->willThrow(new ResourceClassNotFoundException())->shouldBeCalled();

        $decoratedReturnParent = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $decoratedReturnParent->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['foo']))->shouldBeCalled();

        return [
            [null, ['name', 'alias']],
            [$decoratedThrowsNotFound->reveal(), ['name', 'alias']],
            [$decoratedReturnParent->reveal(), ['name', 'alias', 'foo']],
        ];
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\ResourceClassNotFoundException
     * @expectedExceptionMessage The resource class "\DoNotExist" does not exist.
     */
    public function testClassDoesNotExist()
    {
        $reader = $this->prophesize(Reader::class);

        $factory = new AnnotationPropertyNameCollectionFactory($reader->reveal());
        $factory->create('\DoNotExist');
    }
}
