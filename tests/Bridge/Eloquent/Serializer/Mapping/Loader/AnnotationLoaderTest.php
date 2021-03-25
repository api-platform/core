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

namespace ApiPlatform\Core\Tests\Bridge\Eloquent\Serializer\Mapping\Loader;

use ApiPlatform\Core\Bridge\Eloquent\Serializer\Mapping\Loader\AnnotationLoader;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Models\Dummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use Illuminate\Contracts\Queue\QueueableEntity;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Mapping\ClassMetadata;
use Symfony\Component\Serializer\Mapping\Loader\LoaderInterface;

/**
 * @group eloquent
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class AnnotationLoaderTest extends TestCase
{
    use ProphecyTrait;

    private $decoratedLoaderProphecy;
    private $annotationLoader;

    protected function setUp(): void
    {
        $this->decoratedLoaderProphecy = $this->prophesize(LoaderInterface::class);
        $this->annotationLoader = new AnnotationLoader($this->decoratedLoaderProphecy->reveal());
    }

    /**
     * @dataProvider provideLoadClassMetadataCases
     */
    public function testLoadClassMetadata(ClassMetadata $classMetadata, bool $expectedResult): void
    {
        $this->decoratedLoaderProphecy->loadClassMetadata($classMetadata)->willReturn(true);

        self::assertSame($expectedResult, $this->annotationLoader->loadClassMetadata($classMetadata));
    }

    public function provideLoadClassMetadataCases(): \Generator
    {
        yield 'eloquent model' => [new ClassMetadata(Dummy::class), false];

        yield 'interface of eloquent model' => [new ClassMetadata(QueueableEntity::class), false];

        yield 'resource' => [new ClassMetadata(RelatedDummy::class), true];
    }
}
