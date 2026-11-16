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

namespace ApiPlatform\State\Tests;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\State\Exception\ParameterNotSupportedException;
use ApiPlatform\State\Pagination\PaginationOptions;
use ApiPlatform\State\ParameterNotFound;
use ApiPlatform\State\ParameterProviderInterface;
use ApiPlatform\State\Provider\ParameterProvider;
use ApiPlatform\State\ProviderInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

final class ParameterProviderTest extends TestCase
{
    public function testExtractValues(): void
    {
        $locator = new class implements ContainerInterface {
            public function get(string $id)
            {
                if ('test' === $id) {
                    return new class implements ParameterProviderInterface {
                        public function provide(Parameter $parameter, array $parameters = [], array $context = []): Operation
                        {
                            return new Get(name: 'ok');
                        }
                    };
                }
            }

            public function has(string $id): bool
            {
                return 'test' === $id;
            }
        };

        $operation = new Get(parameters: new Parameters([
            'order' => new QueryParameter(key: 'order', provider: 'test'),
            'search[:property]' => new QueryParameter(key: 'search[:property]', provider: [self::class, 'provide']),
            'foo' => new QueryParameter(key: 'foo', provider: [self::class, 'shouldNotBeCalled']),
            'baz' => (new QueryParameter(key: 'baz'))->withExtraProperties(['_api_values' => 'test1']),
            'fas' => (new QueryParameter(key: 'fas'))->withExtraProperties(['_api_values' => '42']),
        ]));
        $parameterProvider = new ParameterProvider(null, $locator);
        $request = new Request(server: ['QUERY_STRING' => 'order[foo]=asc&search[a]=bar&baz=t42']);
        $context = ['request' => $request, 'operation' => $operation];
        $parameterProvider->provide($operation, [], $context);
        $operation = $request->attributes->get('_api_operation');

        $this->assertEquals('ok', $operation->getName());
        $this->assertEquals(['foo' => 'asc'], $operation->getParameters()->get('order', QueryParameter::class)->getValue());
        $this->assertEquals(['a' => 'bar'], $operation->getParameters()->get('search[:property]', QueryParameter::class)->getValue());
        $this->assertEquals('t42', $operation->getParameters()->get('baz', QueryParameter::class)->getValue());
        $this->assertEquals(new ParameterNotFound(), $operation->getParameters()->get('fas', QueryParameter::class)->getValue());
    }

    public function testStrictQueryParameterValidationAllowsPaginationParameters(): void
    {
        $paginationOptions = new PaginationOptions(
            paginationEnabled: true,
            clientItemsPerPage: true,
            paginationClientEnabled: true,
            clientPartialPaginationEnabled: true,
        );

        $operation = new GetCollection(parameters: new Parameters(), strictQueryParameterValidation: true);
        $parameterProvider = new ParameterProvider(null, null, $paginationOptions);
        $request = new Request(server: ['QUERY_STRING' => 'page=2&itemsPerPage=10&pagination=false&partial=true']);
        $context = ['request' => $request, 'operation' => $operation];

        $parameterProvider->provide($operation, [], $context);

        $this->addToAssertionCount(1);
    }

    public function testStrictQueryParameterValidationUsesRenamedPaginationParameters(): void
    {
        // Simulates a global rename, e.g. api_platform.collection.pagination.items_per_page_parameter_name: _limit
        $paginationOptions = new PaginationOptions(
            paginationEnabled: true,
            paginationPageParameterName: '_page',
            clientItemsPerPage: true,
            itemsPerPageParameterName: '_limit',
        );

        $operation = new GetCollection(parameters: new Parameters(), strictQueryParameterValidation: true);
        $parameterProvider = new ParameterProvider(null, null, $paginationOptions);
        $request = new Request(server: ['QUERY_STRING' => '_page=2&_limit=10']);
        $context = ['request' => $request, 'operation' => $operation];

        $parameterProvider->provide($operation, [], $context);
        $this->addToAssertionCount(1);

        // The default names must no longer be implicitly whitelisted once renamed.
        $rejected = new ParameterProvider(null, null, $paginationOptions);
        $request = new Request(server: ['QUERY_STRING' => 'itemsPerPage=10']);
        $context = ['request' => $request, 'operation' => $operation];
        $this->expectException(ParameterNotSupportedException::class);
        $rejected->provide($operation, [], $context);
    }

    public function testStrictQueryParameterValidationStillRejectsUnknownParameters(): void
    {
        $paginationOptions = new PaginationOptions(paginationEnabled: true);
        $operation = new GetCollection(parameters: new Parameters(), strictQueryParameterValidation: true);
        $parameterProvider = new ParameterProvider(null, null, $paginationOptions);
        $request = new Request(server: ['QUERY_STRING' => 'unknown=1']);
        $context = ['request' => $request, 'operation' => $operation];

        $this->expectException(ParameterNotSupportedException::class);
        $parameterProvider->provide($operation, [], $context);
    }

    public function testStrictQueryParameterValidationDoesNotWhitelistPaginationOnItemOperations(): void
    {
        $paginationOptions = new PaginationOptions(paginationEnabled: true);
        $operation = new Get(parameters: new Parameters(), strictQueryParameterValidation: true);
        $parameterProvider = new ParameterProvider(null, null, $paginationOptions);
        $request = new Request(server: ['QUERY_STRING' => 'page=2']);
        $context = ['request' => $request, 'operation' => $operation];

        $this->expectException(ParameterNotSupportedException::class);
        $parameterProvider->provide($operation, [], $context);
    }

    public function testStrictQueryParameterValidationWithoutPaginationOptionsStillRejectsPagination(): void
    {
        $operation = new GetCollection(parameters: new Parameters(), strictQueryParameterValidation: true);
        $parameterProvider = new ParameterProvider();
        $request = new Request(server: ['QUERY_STRING' => 'page=2']);
        $context = ['request' => $request, 'operation' => $operation];

        $this->expectException(ParameterNotSupportedException::class);
        $parameterProvider->provide($operation, [], $context);
    }

    public function testQueryProviderReturningAParameter(): void
    {
        $operation = $this->provideQuery(static fn (Parameter $parameter): Parameter => $parameter->withValue('resolved'));

        $this->assertSame('resolved', $operation->getParameters()->get('a')->getValue());
    }

    public function testQueryProviderReturningAnOperationWithTheReplacedParameter(): void
    {
        $operation = $this->provideQuery(static fn (Parameter $parameter, array $values, array $context): Operation => $context['operation']->withParameters(
            $context['operation']->getParameters()->with('a', $parameter->withValue('from-operation'))
        ));

        $this->assertSame('from-operation', $operation->getParameters()->get('a')->getValue());
    }

    public function testQueryProviderMutatingThePassedParameter(): void
    {
        $operation = $this->provideQuery(static function (Parameter $parameter): void {
            $parameter->setValue('legacy');
        });

        $this->assertSame('legacy', $operation->getParameters()->get('a')->getValue());
    }

    public function testQueryProviderReturningAnOperationWithNewParametersIsHonoured(): void
    {
        $operation = $this->provideQuery(static fn (Parameter $parameter, array $values, array $context): Operation => $context['operation']->withParameters(new Parameters([
            'a' => $parameter->withValue('resolved'),
            'extra' => new QueryParameter(key: 'extra'),
        ])));

        $this->assertSame('resolved', $operation->getParameters()->get('a')->getValue());
        $this->assertTrue($operation->getParameters()->has('extra'));
    }

    public function testQueryProviderDoesNotWriteIntoTheOperationParameters(): void
    {
        $original = new QueryParameter(key: 'a', provider: static fn (Parameter $parameter): Parameter => $parameter->withValue('resolved'));
        $operation = new Get(parameters: new Parameters(['a' => $original]));
        $request = new Request(server: ['QUERY_STRING' => 'a=raw']);

        (new ParameterProvider())->provide($operation, [], ['request' => $request, 'operation' => $operation]);

        $this->assertSame($original, $operation->getParameters()->get('a'));
        $this->assertInstanceOf(ParameterNotFound::class, $original->getValue());
    }

    public function testPathProviderReturningAParameter(): void
    {
        $uriVariables = $this->providePath(static fn (Parameter $parameter): Parameter => $parameter->withValue(42));

        $this->assertSame(42, $uriVariables['id']);
    }

    public function testPathProviderReturningAnOperationWithTheReplacedUriVariable(): void
    {
        $uriVariables = $this->providePath(static fn (Parameter $parameter, array $values, array $context): Operation => $context['operation']->withUriVariables([
            'id' => $parameter->withValue(7),
        ]));

        $this->assertSame(7, $uriVariables['id']);
    }

    public function testPathProviderMutatingThePassedParameter(): void
    {
        $uriVariables = $this->providePath(static function (Parameter $parameter): void {
            $parameter->setValue('legacy');
        });

        $this->assertSame('legacy', $uriVariables['id']);
    }

    private function provideQuery(callable $provider): Operation
    {
        $operation = new Get(parameters: new Parameters(['a' => new QueryParameter(key: 'a', provider: $provider)]));
        $request = new Request(server: ['QUERY_STRING' => 'a=raw']);

        (new ParameterProvider())->provide($operation, [], ['request' => $request, 'operation' => $operation]);

        return $request->attributes->get('_api_operation');
    }

    /**
     * @return array<string, mixed>
     */
    private function providePath(callable $provider): array
    {
        $operation = new Get(uriVariables: ['id' => new Link(parameterName: 'id', provider: $provider)]);
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->expects($this->once())->method('provide')->willReturnCallback(static function (Operation $operation, array $uriVariables) use (&$captured): null {
            $captured = $uriVariables;

            return null;
        });

        (new ParameterProvider($decorated))->provide($operation, ['id' => '1'], ['request' => new Request(), 'operation' => $operation]);

        return $captured;
    }

    public static function provide(): void
    {
        static::assertTrue(true);
    }

    public static function shouldNotBeCalled(): void
    {
        static::assertTrue(false); // @phpstan-ignore-line
    }
}
