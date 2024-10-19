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


use ApiPlatform\Serializer\RequestCacheSerializerContextBuilder;
use ApiPlatform\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Serializer\Tests\Fixtures\ApiResource\DummyGroup;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Maxime Hélias <maximehelias16@gmail.com>
 */
class RequestCacheSerializerContextBuilderTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateFromRequestWithoutCache(): void
    {
        $request = new Request();

        $attributes = [
            'resource_class' => DummyGroup::class,
            'operation_name' => 'get',
        ];

        $decoratedProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $decoratedProphecy->createFromRequest($request, true, $attributes)->willReturn($expected = ['foo' => 'bar'])->shouldBeCalled();

        $serializerContextBuilderFilter = new RequestCacheSerializerContextBuilder($decoratedProphecy->reveal());
        $context = $serializerContextBuilderFilter->createFromRequest($request, true, $attributes);

        $this->assertTrue($request->attributes->has('_api_normalization_context'));
        $this->assertSame($expected, $context);
    }

    public function testCreateFromRequestWithCache(): void
    {
        $request = new Request();
        $request->attributes->set('_api_normalization_context', $expected = ['foo' => 'bar']);

        $attributes = [
            'resource_class' => DummyGroup::class,
            'operation_name' => 'get',
        ];

        $decoratedProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $decoratedProphecy->createFromRequest($request, true, $attributes)->willReturn([])->shouldNotBeCalled();

        $serializerContextBuilderFilter = new RequestCacheSerializerContextBuilder($decoratedProphecy->reveal());
        $context = $serializerContextBuilderFilter->createFromRequest($request, true, $attributes);

        $this->assertSame($expected, $context);
    }
}
