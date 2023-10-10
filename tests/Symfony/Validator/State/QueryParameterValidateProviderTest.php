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

namespace ApiPlatform\Tests\Symfony\Validator\State;

use ApiPlatform\Api\QueryParameterValidator\QueryParameterValidator;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Symfony\Validator\State\QueryParameterValidateProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ServerBag;

class QueryParameterValidateProviderTest extends TestCase
{
    public function testValidate(): void
    {
        $filters = ['test'];
        $operation = new GetCollection(filters: $filters, class: 'foo');
        $request = $this->createMock(Request::class);
        $request->server = $this->createMock(ServerBag::class);
        $request->server->method('get')->with('QUERY_STRING')->willReturn('foo=bar');
        $request->method('isMethodSafe')->willReturn(true);
        $request->method('getMethod')->willReturn('GET');
        $context = ['request' => $request];
        $obj = new \stdClass();
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->method('provide')->willReturn($obj);
        $validator = $this->createMock(QueryParameterValidator::class);
        $validator->expects($this->once())->method('validateFilters')->with('foo', $filters, ['foo' => 'bar']);
        $provider = new QueryParameterValidateProvider($decorated, $validator);
        $provider->provide($operation, [], $context);
    }
}
