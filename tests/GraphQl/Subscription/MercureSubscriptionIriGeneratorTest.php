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
use Symfony\Component\Routing\RequestContext;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class MercureSubscriptionIriGeneratorTest extends TestCase
{
    private $requestContext;
    private $hubUrl;
    private $mercureSubscriptionIriGenerator;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->requestContext = new RequestContext('', 'GET', 'example.com');
        $this->hubUrl = 'https://demo.mercure.rocks/hub';
        $this->mercureSubscriptionIriGenerator = new MercureSubscriptionIriGenerator($this->requestContext, $this->hubUrl);
    }

    public function testGenerateTopicIri(): void
    {
        $this->assertSame('http://example.com/subscriptions/subscription-id', $this->mercureSubscriptionIriGenerator->generateTopicIri('subscription-id'));
    }

    public function testGenerateDefaultTopicIri(): void
    {
        $mercureSubscriptionIriGenerator = new MercureSubscriptionIriGenerator(new RequestContext('', 'GET', '', ''), $this->hubUrl);

        $this->assertSame('https://api-platform.com/subscriptions/subscription-id', $mercureSubscriptionIriGenerator->generateTopicIri('subscription-id'));
    }

    public function testGenerateMercureUrl(): void
    {
        $this->assertSame("$this->hubUrl?topic=http://example.com/subscriptions/subscription-id", $this->mercureSubscriptionIriGenerator->generateMercureUrl('subscription-id'));
    }
}
