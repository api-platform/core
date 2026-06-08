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

namespace ApiPlatform\JsonApi\Tests\State;

use ApiPlatform\JsonApi\State\JsonApiProvider;
use ApiPlatform\Metadata\Get;
use ApiPlatform\State\ProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class JsonApiProviderTest extends TestCase
{
    public function testProvide(): void
    {
        $request = new Request(query: ['fields' => ['dummy' => 'id,name,dummyFloat', 'relatedDummy' => 'id,name'], 'include' => 'relatedDummy,foo']);
        $request->setRequestFormat('jsonapi');
        $operation = new Get(class: \stdClass::class, shortName: 'dummy');
        $context = ['request' => $request];
        $decorated = $this->createMock(ProviderInterface::class);
        $provider = new JsonApiProvider($decorated);
        $provider->provide($operation, [], $context);

        $this->assertSame(['id', 'name', 'dummyFloat', 'relatedDummy' => ['id', 'name']], $request->attributes->get('_api_filter_property'));
        $this->assertSame(['relatedDummy'], $request->attributes->get('_api_included'));
    }

    public function testProvideMergesFlatPaginationWithBracketFilter(): void
    {
        $request = new Request(['page' => '2', 'itemsPerPage' => '5', 'pagination' => 'true', 'filter' => ['custom' => 'true']]);
        $request->setRequestFormat('jsonapi');

        $operation = new Get(class: \stdClass::class, shortName: 'dummy');
        $context = ['request' => $request];
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->expects($this->once())->method('provide')->with($operation, [], $context);

        $provider = new JsonApiProvider($decorated);
        $provider->provide($operation, [], $context);

        $this->assertSame([
            'custom' => 'true',
            'page' => '2',
            'itemsPerPage' => '5',
            'pagination' => 'true',
        ], $request->attributes->get('_api_filters'));
    }

    // #8216: flat custom params must survive when _api_filters is pre-set by JSON:API.
    public function testProvidePreservesFlatCustomQueryParamsWithoutBracketFilter(): void
    {
        $request = Request::create('/sessions?city_id=3152&order[distance]=asc&page=1');
        $request->setRequestFormat('jsonapi');

        $operation = new Get(class: \stdClass::class, shortName: 'dummy');
        $context = ['request' => $request];
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->expects($this->once())->method('provide')->with($operation, [], $context);

        $provider = new JsonApiProvider($decorated);
        $provider->provide($operation, [], $context);

        $filters = $request->attributes->get('_api_filters');

        $this->assertIsArray($filters);
        $this->assertSame('3152', $filters['city_id'] ?? null);
        $this->assertSame(['distance' => 'asc'], $filters['order'] ?? null);
        $this->assertSame('1', $filters['page'] ?? null);
    }

    // _api_query_parameters set by an earlier listener / ParameterProvider must be reused.
    public function testProvideHonoursPrePopulatedApiQueryParameters(): void
    {
        $request = Request::create('/sessions?page=1');
        $request->setRequestFormat('jsonapi');
        $request->attributes->set('_api_query_parameters', ['custom_override' => 'yes', 'page' => '1']);

        $operation = new Get(class: \stdClass::class, shortName: 'dummy');
        $context = ['request' => $request];
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->expects($this->once())->method('provide')->with($operation, [], $context);

        $provider = new JsonApiProvider($decorated);
        $provider->provide($operation, [], $context);

        $filters = $request->attributes->get('_api_filters');

        $this->assertSame('yes', $filters['custom_override'] ?? null);
        $this->assertSame('1', $filters['page'] ?? null);
    }

    public function testProvideDoesNotReinjectBracketPageAfterHoisting(): void
    {
        $request = Request::create('/dummies?page[itemsPerPage]=15');
        $request->setRequestFormat('jsonapi');

        $operation = new Get(class: \stdClass::class, shortName: 'dummy');
        $context = ['request' => $request];
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->expects($this->once())->method('provide')->with($operation, [], $context);

        $provider = new JsonApiProvider($decorated);
        $provider->provide($operation, [], $context);

        $filters = $request->attributes->get('_api_filters');

        $this->assertSame('15', $filters['itemsPerPage'] ?? null);
        $this->assertArrayNotHasKey('page', $filters);
    }
}
