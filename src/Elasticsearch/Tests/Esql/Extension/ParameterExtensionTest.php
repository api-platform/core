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

namespace ApiPlatform\Elasticsearch\Tests\Esql\Extension;

use ApiPlatform\Elasticsearch\Esql\EsqlQuery;
use ApiPlatform\Elasticsearch\Esql\Extension\ParameterExtension;
use ApiPlatform\Elasticsearch\Esql\Filter\ExactFilter;
use ApiPlatform\Elasticsearch\Tests\Fixtures\Foo;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\TypeInfo\Type;

class ParameterExtensionTest extends TestCase
{
    public function testAppliesParameterFilters(): void
    {
        $query = new EsqlQuery('foo', []);
        $parameter = (new QueryParameter(key: 'genre', property: 'genre', filter: new ExactFilter()))->setValue('fantasy');
        $operation = (new GetCollection())->withParameters(['genre' => $parameter]);

        $nameConverter = $this->createMock(NameConverterInterface::class);
        $nameConverter->expects($this->once())->method('normalize')->with('genre', Foo::class, null, [])->willReturn('genre_field');

        $this->createExtension(nameConverter: $nameConverter)->applyToCollection($query, Foo::class, $operation);

        self::assertSame([
            'query' => 'FROM foo | WHERE ??f1 == ?p2',
            'params' => [['f1' => 'genre_field'], ['p2' => 'fantasy']],
        ], $query->compile());
    }

    public function testSkipsParameterWithoutValueOrFilter(): void
    {
        $query = new EsqlQuery('foo', []);
        $operation = (new GetCollection())->withParameters([
            'nofilter' => (new QueryParameter(key: 'nofilter', property: 'genre'))->setValue('x'),
            'novalue' => new QueryParameter(key: 'novalue', property: 'genre', filter: new ExactFilter()),
        ]);

        $this->createExtension()->applyToCollection($query, Foo::class, $operation);

        self::assertSame('FROM foo', $query->compile()['query']);
    }

    public function testRejectsNestedField(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/not supported by ES\|QL/');

        $query = new EsqlQuery('foo', []);
        $parameter = (new QueryParameter(key: 'bar.baz', property: 'bar.baz', filter: new ExactFilter()))->setValue('x');
        $operation = (new GetCollection())->withParameters(['bar.baz' => $parameter]);

        $propertyMetadataFactory = $this->createStub(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->method('create')->willReturn((new ApiProperty())->withNativeType(Type::list(Type::object(Foo::class))));

        $resourceClassResolver = $this->createStub(ResourceClassResolverInterface::class);
        $resourceClassResolver->method('isResourceClass')->willReturn(true);

        $this->createExtension($propertyMetadataFactory, $resourceClassResolver)->applyToCollection($query, Foo::class, $operation);
    }

    private function createExtension(
        ?PropertyMetadataFactoryInterface $propertyMetadataFactory = null,
        ?ResourceClassResolverInterface $resourceClassResolver = null,
        ?NameConverterInterface $nameConverter = null,
    ): ParameterExtension {
        return new ParameterExtension(
            $this->createStub(ContainerInterface::class),
            $propertyMetadataFactory ?? $this->createStub(PropertyMetadataFactoryInterface::class),
            $resourceClassResolver ?? $this->createStub(ResourceClassResolverInterface::class),
            $nameConverter,
        );
    }
}
