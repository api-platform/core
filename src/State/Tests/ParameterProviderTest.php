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
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\State\ParameterNotFound;
use ApiPlatform\State\ParameterProviderInterface;
use ApiPlatform\State\Provider\ParameterProvider;
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

    public static function provide(): void
    {
        static::assertTrue(true);
    }

    public static function shouldNotBeCalled(): void
    {
        static::assertTrue(false); // @phpstan-ignore-line
    }
}
