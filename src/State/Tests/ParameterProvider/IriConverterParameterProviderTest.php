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

namespace ApiPlatform\State\Tests\ParameterProvider;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\State\ParameterProvider\IriConverterParameterProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class IriConverterParameterProviderTest extends TestCase
{
    public function testQueryParameterIsNotMutated(): void
    {
        $resource = new \stdClass();
        $iriConverter = $this->createStub(IriConverterInterface::class);
        $iriConverter->method('getResourceFromIri')->willReturn($resource);

        $parameter = (new QueryParameter(key: 'a'))->withValue('/things/1');
        $operation = new Get(parameters: new Parameters(['a' => $parameter]));

        $result = (new IriConverterParameterProvider($iriConverter, new NullLogger()))->provide($parameter, [], ['operation' => $operation]);

        $this->assertSame('/things/1', $parameter->getValue());
        $this->assertSame($resource, $result->getParameters()->get('a')->getValue());
        $this->assertSame($parameter, $operation->getParameters()->get('a'));
    }

    public function testLinkIsNotMutated(): void
    {
        $resource = new \stdClass();
        $iriConverter = $this->createStub(IriConverterInterface::class);
        $iriConverter->method('getResourceFromIri')->willReturn($resource);

        $link = (new Link(parameterName: 'id'))->withKey('id')->withValue('/things/1');
        $operation = new Get(uriVariables: ['id' => $link]);

        $result = (new IriConverterParameterProvider($iriConverter, new NullLogger()))->provide($link, [], ['operation' => $operation]);

        $this->assertSame('/things/1', $link->getValue());
        $this->assertInstanceOf(HttpOperation::class, $result);
        $this->assertSame($resource, $result->getUriVariables()['id']->getValue());
    }
}
