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

namespace ApiPlatform\Tests\State\Provider;

use ApiPlatform\Metadata\Post;
use ApiPlatform\State\Provider\ContentNegotiationProvider;
use ApiPlatform\State\ProviderInterface;
use Negotiation\Negotiator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;

class ContentNegotiationProviderTest extends TestCase
{
    use ProphecyTrait;

    public function testRequestWithEmptyContentType(): void
    {
        $expectedResult = new \stdClass();

        $decorated = $this->prophesize(ProviderInterface::class);
        $decorated->provide(Argument::cetera())->willReturn($expectedResult);

        $negotiator = new Negotiator();
        $formats = ['jsonld' => ['application/ld+json']];
        $errorFormats = ['jsonld' => ['application/ld+json']];

        $provider = new ContentNegotiationProvider($decorated->reveal(), $negotiator, $formats, $errorFormats);

        // in Symfony (at least up to 7.0.2, 6.4.2, 6.3.11, 5.4.34), a request
        // without a content-type and content-length header will result in the
        // variables set to an empty string, not null

        $request = new Request(
            server: [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/',
                'CONTENT_TYPE' => '',
                'CONTENT_LENGTH' => '',
            ],
            content: ''
        );

        $operation = new Post();
        $context = ['request' => $request];

        $result = $provider->provide($operation, [], $context);

        $this->assertSame($expectedResult, $result);
    }
}
