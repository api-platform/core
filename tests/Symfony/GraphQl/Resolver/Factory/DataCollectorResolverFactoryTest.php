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

namespace ApiPlatform\Tests\Symfony\GraphQl\Resolver\Factory;

use ApiPlatform\GraphQl\Resolver\Factory\ResolverFactoryInterface;
use ApiPlatform\Symfony\GraphQl\Resolver\Factory\DataCollectorResolverFactory;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class DataCollectorResolverFactoryTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $requestStack;
    private ObjectProphecy $resolverFactory;
    private DataCollectorResolverFactory $dataCollectorResolverFactory;

    protected function setUp(): void
    {
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->resolverFactory = $this->prophesize(ResolverFactoryInterface::class);
        $this->dataCollectorResolverFactory = new DataCollectorResolverFactory($this->resolverFactory->reveal(), $this->requestStack->reveal());
    }

    public function testDataCollectorAddDataInsideRequestAttribute(): void
    {
        $request = new Request();
        $this->requestStack->getCurrentRequest()->willReturn($request);
        $this->resolverFactory->__invoke(Dummy::class, null, null)->willReturn(static fn (?array $source, array $args, $context, ResolveInfo $info): array => $args);

        $result = $this->dataCollectorResolverFactory->__invoke(Dummy::class)(null, ['bar'], [], $this->prophesize(ResolveInfo::class)->reveal());

        $this->assertEquals(['bar'], $result);
        $this->assertEquals([Dummy::class => ['bar']], $request->attributes->get('_graphql_args'));
    }
}
