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
use Symfony\Component\Mercure\Hub;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Routing\RequestContext;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class MercureSubscriptionIriGeneratorTest extends TestCase
{
    private $requestContext;
    private $defaultHub;
    private $managedHub;
    private $registry;
    private $mercureSubscriptionIriGenerator;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        if (!class_exists(Hub::class)) {
            $this->markTestSkipped();
        }

        $this->defaultHub = new Hub('https://demo.mercure.rocks/hub', new StaticTokenProvider('xx'));
        $this->managedHub = new Hub('https://demo.mercure.rocks/managed', new StaticTokenProvider('xx'));

        $this->registry = new HubRegistry($this->defaultHub, ['default' => $this->defaultHub, 'managed' => $this->managedHub]);

        $this->requestContext = new RequestContext('', 'GET', 'example.com');
        $this->mercureSubscriptionIriGenerator = new MercureSubscriptionIriGenerator($this->requestContext, $this->registry);
    }

    /**
     * @group legacy
     */
    public function testGenerateTopicIriWithLegacySignature(): void
    {
        $mercureSubscriptionIriGenerator = new MercureSubscriptionIriGenerator(new RequestContext('', 'GET', 'example.com'), 'https://example.com/.well-known/mercure');

        $this->assertSame('http://example.com/subscriptions/subscription-id', $mercureSubscriptionIriGenerator->generateTopicIri('subscription-id'));
    }

    /**
     * @group legacy
     */
    public function testGenerateDefaultTopicIriWithLegacySignature(): void
    {
        $mercureSubscriptionIriGenerator = new MercureSubscriptionIriGenerator(new RequestContext('', 'GET', '', ''), 'https://example.com/.well-known/mercure');

        $this->assertSame('https://api-platform.com/subscriptions/subscription-id', $mercureSubscriptionIriGenerator->generateTopicIri('subscription-id'));
    }

    /**
     * @group legacy
     */
    public function testGenerateMercureUrlWithLegacySignature(): void
    {
        $mercureSubscriptionIriGenerator = new MercureSubscriptionIriGenerator(new RequestContext('', 'GET', 'example.com'), 'https://example.com/.well-known/mercure');

        $this->assertSame('https://example.com/.well-known/mercure?topic=http://example.com/subscriptions/subscription-id', $mercureSubscriptionIriGenerator->generateMercureUrl('subscription-id'));
    }

    public function testGenerateTopicIri(): void
    {
        if (!class_exists(Hub::class)) {
            $this->markTestSkipped();
        }

        $this->assertSame('http://example.com/subscriptions/subscription-id', $this->mercureSubscriptionIriGenerator->generateTopicIri('subscription-id'));
    }

    public function testGenerateDefaultTopicIri(): void
    {
        if (!class_exists(Hub::class)) {
            $this->markTestSkipped();
        }

        $mercureSubscriptionIriGenerator = new MercureSubscriptionIriGenerator(new RequestContext('', 'GET', '', ''), $this->registry);

        $this->assertSame('https://api-platform.com/subscriptions/subscription-id', $mercureSubscriptionIriGenerator->generateTopicIri('subscription-id'));
    }

    public function testGenerateMercureUrl(): void
    {
        if (!class_exists(Hub::class)) {
            $this->markTestSkipped();
        }

        $this->assertSame("{$this->defaultHub->getUrl()}?topic=http://example.com/subscriptions/subscription-id", $this->mercureSubscriptionIriGenerator->generateMercureUrl('subscription-id'));
    }

    public function testGenerateExplicitDefaultMercureUrl(): void
    {
        if (!class_exists(Hub::class)) {
            $this->markTestSkipped();
        }

        $this->assertSame("{$this->defaultHub->getUrl()}?topic=http://example.com/subscriptions/subscription-id", $this->mercureSubscriptionIriGenerator->generateMercureUrl('subscription-id', 'default'));
    }

    public function testGenerateNonDefaultMercureUrl(): void
    {
        if (!class_exists(Hub::class)) {
            $this->markTestSkipped();
        }

        $this->assertSame("{$this->managedHub->getUrl()}?topic=http://example.com/subscriptions/subscription-id", $this->mercureSubscriptionIriGenerator->generateMercureUrl('subscription-id', 'managed'));
    }
}
