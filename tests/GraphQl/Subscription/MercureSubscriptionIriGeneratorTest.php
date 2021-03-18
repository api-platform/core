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

namespace ApiPlatform\Core\Tests\GraphQl\Subscription;

use ApiPlatform\Core\GraphQl\Subscription\MercureSubscriptionIriGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MercureBundle\Mercure;
use Symfony\Component\Mercure\Hub;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Routing\RequestContext;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class MercureSubscriptionIriGeneratorTest extends TestCase
{
    private $requestContext;
    private $hubs;
    private $publishers;
    private $factories;
    private $defaultHub;
    private $managedHub;
    private $mercure;
    private $mercureSubscriptionIriGenerator;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->hubs = $this->prophesize(ServiceProviderInterface::class);
        $this->publishers = $this->prophesize(ServiceProviderInterface::class);
        $this->factories = $this->prophesize(ServiceProviderInterface::class);

        $this->defaultHub = new Hub('https://demo.mercure.rocks/hub', new StaticTokenProvider('xx'));
        $this->managedHub = new Hub('https://demo.mercure.rocks/managed', new StaticTokenProvider('xx'));

        $this->mercure = new Mercure('default', $this->hubs->reveal(), $this->publishers->reveal(), $this->factories->reveal());

        $this->requestContext = new RequestContext('', 'GET', 'example.com');
        $this->mercureSubscriptionIriGenerator = new MercureSubscriptionIriGenerator($this->requestContext, $this->mercure);
    }

    public function testGenerateTopicIri(): void
    {
        $this->assertSame('http://example.com/subscriptions/subscription-id', $this->mercureSubscriptionIriGenerator->generateTopicIri('subscription-id'));
    }

    public function testGenerateDefaultTopicIri(): void
    {
        $mercureSubscriptionIriGenerator = new MercureSubscriptionIriGenerator(new RequestContext('', 'GET', '', ''), $this->mercure);

        $this->assertSame('https://api-platform.com/subscriptions/subscription-id', $mercureSubscriptionIriGenerator->generateTopicIri('subscription-id'));
    }

    public function testGenerateMercureUrl(): void
    {
        $this->hubs->has('default')->shouldBeCalled()->willReturn(true);
        $this->hubs->get('default')->shouldBeCalled()->willReturn($this->defaultHub);

        $this->assertSame("{$this->defaultHub->getUrl()}?topic=http://example.com/subscriptions/subscription-id", $this->mercureSubscriptionIriGenerator->generateMercureUrl('subscription-id'));
    }

    public function testGenerateExplicitDefaultMercureUrl(): void
    {
        $this->hubs->has('default')->shouldBeCalled()->willReturn(true);
        $this->hubs->get('default')->shouldBeCalled()->willReturn($this->defaultHub);

        $this->assertSame("{$this->defaultHub->getUrl()}?topic=http://example.com/subscriptions/subscription-id", $this->mercureSubscriptionIriGenerator->generateMercureUrl('subscription-id', 'default'));
    }

    public function testGenerateNonDefaultMercureUrl(): void
    {
        $this->hubs->has('managed')->shouldBeCalled()->willReturn(true);
        $this->hubs->get('managed')->shouldBeCalled()->willReturn($this->managedHub);

        $this->assertSame("{$this->managedHub->getUrl()}?topic=http://example.com/subscriptions/subscription-id", $this->mercureSubscriptionIriGenerator->generateMercureUrl('subscription-id', 'managed'));
    }
}
