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

namespace ApiPlatform\Tests\JsonApi\State;

use ApiPlatform\JsonApi\State\JsonApiProvider;
use ApiPlatform\Metadata\Get;
use ApiPlatform\State\ProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class JsonApiProviderTest extends TestCase
{
    public function testProvide(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('getRequestFormat')->willReturn('jsonapi');
        $request->attributes = $this->createMock(ParameterBag::class);
        $request->attributes->expects($this->once())->method('get')->with('_api_filters', [])->willReturn([]);
        $request->attributes->method('set')->with($this->logicalOr('_api_filter_property', '_api_included', '_api_filters'), $this->logicalOr(['id', 'name', 'dummyFloat', 'relatedDummy' => ['id', 'name']], ['relatedDummy'], []));
        $request->query = $this->createMock(ParameterBag::class); // @phpstan-ignore-line
        $request->query->method('all')->willReturn(['fields' => ['dummy' => 'id,name,dummyFloat', 'relatedDummy' => 'id,name'], 'include' => 'relatedDummy,foo']);
        $operation = new Get(class: 'dummy', shortName: 'dummy');
        $context = ['request' => $request];
        $decorated = $this->createMock(ProviderInterface::class);
        $provider = new JsonApiProvider($decorated);
        $provider->provide($operation, [], $context);
    }
}
