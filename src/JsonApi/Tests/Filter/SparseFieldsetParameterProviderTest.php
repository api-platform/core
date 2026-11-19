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

namespace ApiPlatform\JsonApi\Tests\Filter;

use ApiPlatform\JsonApi\Filter\SparseFieldsetParameterProvider;
use ApiPlatform\JsonApi\Util\ResourceLinkageResolver;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\TypeInfo\Type;

final class SparseFieldsetIncludeArticleFixture
{
    public ?SparseFieldsetIncludeAuthorFixture $author = null;
}

final class SparseFieldsetIncludeAuthorFixture
{
    public string $name = '';
}

final class SparseFieldsetParameterProviderTest extends TestCase
{
    // #7267: fields[TYPE] names a resource TYPE ("SparseFieldsetIncludeAuthorFixture"), but
    // the serializer's ATTRIBUTES whitelist is matched against the host's PROPERTY name
    // ("author"). Without resolving TYPE back to the property, the whitelist excludes the
    // relation itself and it never gets included at all.
    public function testFieldsTypeKeyIsResolvedToItsHostPropertyName(): void
    {
        $operation = new Get(class: SparseFieldsetIncludeArticleFixture::class, shortName: 'SparseFieldsetIncludeArticle');

        // The TYPE ("SparseFieldsetIncludeAuthor") intentionally differs from the relation
        // property name ("author") — this is the spec-correct case that was broken.
        $provider = $this->createProvider(authorShortName: 'SparseFieldsetIncludeAuthor');

        $parameter = (new QueryParameter(key: 'fields'))
            ->withProperties(['title'])
            ->setValue(['SparseFieldsetIncludeAuthor' => 'name']);

        $result = $provider->provide($parameter, [], ['operation' => $operation]);

        $this->assertNotNull($result);
        $this->assertSame(
            ['author' => ['name']],
            $result->getNormalizationContext()[AbstractNormalizer::ATTRIBUTES] ?? null,
        );
    }

    // BC: Laravel's existing fixtures key fields[] by the relation NAME, which coincides
    // with the lowercased TYPE (e.g. Book's "author" relation vs. the "Author" resource) —
    // that must keep resolving to itself, exactly as before this fix.
    public function testFieldsKeyMatchingThePropertyNameStillWorks(): void
    {
        $operation = new Get(class: SparseFieldsetIncludeArticleFixture::class, shortName: 'SparseFieldsetIncludeArticle');

        $provider = $this->createProvider(authorShortName: 'Author');

        $parameter = (new QueryParameter(key: 'fields'))
            ->withProperties(['title'])
            ->setValue(['author' => 'name']);

        $result = $provider->provide($parameter, [], ['operation' => $operation]);

        $this->assertNotNull($result);
        $this->assertSame(
            ['author' => ['name']],
            $result->getNormalizationContext()[AbstractNormalizer::ATTRIBUTES] ?? null,
        );
    }

    private function createProvider(string $authorShortName): SparseFieldsetParameterProvider
    {
        $articleClass = SparseFieldsetIncludeArticleFixture::class;
        $authorClass = SparseFieldsetIncludeAuthorFixture::class;

        $propertyNameCollectionFactory = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory->method('create')->willReturnCallback(
            static fn (string $class): PropertyNameCollection => match ($class) {
                $articleClass => new PropertyNameCollection(['author']),
                $authorClass => new PropertyNameCollection(['name']),
                default => new PropertyNameCollection([]),
            },
        );

        $authorRelationProperty = (new ApiProperty())
            ->withNativeType(Type::nullable(Type::object($authorClass)))
            ->withReadable(true);
        $authorNameProperty = (new ApiProperty())->withReadable(true);

        $propertyMetadataFactory = $this->createStub(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->method('create')->willReturnCallback(
            static fn (string $class, string $property): ApiProperty => match (true) {
                $articleClass === $class && 'author' === $property => $authorRelationProperty,
                $authorClass === $class && 'name' === $property => $authorNameProperty,
                default => new ApiProperty(),
            },
        );

        $resourceNameCollectionFactory = $this->createStub(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactory->method('create')->willReturn(new ResourceNameCollection([$articleClass, $authorClass]));

        $articleMetadata = new ApiResource(shortName: 'SparseFieldsetIncludeArticle', class: $articleClass, operations: ['get' => new Get(shortName: 'SparseFieldsetIncludeArticle', class: $articleClass)]);
        $authorMetadata = new ApiResource(shortName: $authorShortName, class: $authorClass, operations: ['get' => new Get(shortName: $authorShortName, class: $authorClass)]);

        $resourceMetadataCollectionFactory = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactory->method('create')->willReturnCallback(
            static fn (string $class): ResourceMetadataCollection => match ($class) {
                $articleClass => new ResourceMetadataCollection($articleClass, [$articleMetadata]),
                $authorClass => new ResourceMetadataCollection($authorClass, [$authorMetadata]),
                default => new ResourceMetadataCollection($class),
            },
        );

        $resourceClassResolver = $this->createStub(ResourceClassResolverInterface::class);
        $resourceClassResolver->method('isResourceClass')->willReturnCallback(
            static fn (string $class): bool => $authorClass === $class,
        );

        return new SparseFieldsetParameterProvider(
            $resourceNameCollectionFactory,
            $resourceMetadataCollectionFactory,
            $propertyNameCollectionFactory,
            $propertyMetadataFactory,
            new ResourceLinkageResolver($resourceClassResolver),
        );
    }
}
