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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\State\ParameterProvider\ReadLinkParameterProvider;
use ApiPlatform\State\ProviderInterface;
use PHPUnit\Framework\TestCase;

final class ReadLinkParameterProviderTest extends TestCase
{
    public function testQueryParameterIsNotMutated(): void
    {
        $relation = new \stdClass();
        $parameter = (new QueryParameter(key: 'a', extraProperties: ['resource_class' => \stdClass::class]))->withValue('1');
        $operation = new Get(class: \stdClass::class, parameters: new Parameters(['a' => $parameter]));

        $result = $this->createProvider($relation)->provide($parameter, [], ['operation' => $operation]);

        $this->assertSame('1', $parameter->getValue());
        $this->assertSame($relation, $result->getParameters()->get('a')->getValue());
        $this->assertSame($parameter, $operation->getParameters()->get('a'));
    }

    public function testLinkIsNotMutated(): void
    {
        $relation = new \stdClass();
        $link = (new Link(parameterName: 'id', fromClass: \stdClass::class))->withKey('id')->withValue('1');
        $operation = new Get(class: \stdClass::class, uriVariables: ['id' => $link]);

        $result = $this->createProvider($relation)->provide($link, [], ['operation' => $operation]);

        $this->assertSame('1', $link->getValue());
        $this->assertInstanceOf(HttpOperation::class, $result);
        $this->assertSame($relation, $result->getUriVariables()['id']->getValue());
    }

    private function createProvider(object $relation): ReadLinkParameterProvider
    {
        $locator = $this->createStub(ProviderInterface::class);
        $locator->method('provide')->willReturn($relation);

        $factory = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $factory->method('create')->willReturn(new ResourceMetadataCollection(\stdClass::class, [
            (new ApiResource(class: \stdClass::class))->withOperations(new Operations(['get' => new Get(name: 'get', class: \stdClass::class, uriVariables: ['id' => new Link(parameterName: 'id', fromClass: \stdClass::class)])])),
        ]));

        return new ReadLinkParameterProvider($locator, $factory);
    }
}
