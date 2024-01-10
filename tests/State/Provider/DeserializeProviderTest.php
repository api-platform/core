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
use ApiPlatform\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\State\Provider\DeserializeProvider;
use ApiPlatform\State\ProviderInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Serializer\SerializerInterface;

class DeserializeProviderTest extends TestCase
{
    use ProphecyTrait;

    public function testRequestWithEmptyContentType(): void
    {
        $expectedResult = new \stdClass();

        $decorated = $this->prophesize(ProviderInterface::class);
        $decorated->provide(Argument::cetera())->willReturn($expectedResult);

        $serializer = $this->prophesize(SerializerInterface::class);
        $serializerContextBuilder = $this->prophesize(SerializerContextBuilderInterface::class);

        $provider = new DeserializeProvider($decorated->reveal(), $serializer->reveal(), $serializerContextBuilder->reveal());

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

        $operation = new Post(deserialize: true);
        $context = ['request' => $request];

        $this->expectException(UnsupportedMediaTypeHttpException::class);
        $result = $provider->provide($operation, [], $context);
    }
}
