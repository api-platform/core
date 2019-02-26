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

namespace ApiPlatform\Core\Tests\Annotation;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Tests\Fixtures\AnnotatedClass;
use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit\Framework\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ApiResourceTest extends TestCase
{
    public function testConstruct()
    {
        $resource = new ApiResource([
            'accessControl' => 'has_role("ROLE_FOO")',
            'accessControlMessage' => 'You are not foo.',
            'attributes' => ['foo' => 'bar', 'validation_groups' => ['baz', 'qux'], 'cache_headers' => ['max_age' => 0, 'shared_max_age' => 0]],
            'collectionOperations' => ['bar' => ['foo']],
            'denormalizationContext' => ['groups' => ['foo']],
            'description' => 'description',
            'fetchPartial' => true,
            'forceEager' => false,
            'formats' => ['foo', 'bar' => ['application/bar']],
            'filters' => ['foo', 'bar'],
            'graphql' => ['query' => ['normalization_context' => ['groups' => ['foo', 'bar']]]],
            'input' => 'Foo',
            'iri' => 'http://example.com/res',
            'itemOperations' => ['foo' => ['bar']],
            'maximumItemsPerPage' => 42,
            'mercure' => '[\'foo\', object.owner]',
            'messenger' => true,
            'normalizationContext' => ['groups' => ['bar']],
            'order' => ['foo', 'bar' => 'ASC'],
            'openapiContext' => ['description' => 'foo'],
            'output' => 'Bar',
            'paginationClientEnabled' => true,
            'paginationClientItemsPerPage' => true,
            'paginationClientPartial' => true,
            'paginationEnabled' => true,
            'paginationFetchJoinCollection' => true,
            'paginationItemsPerPage' => 42,
            'paginationPartial' => true,
            'routePrefix' => '/foo',
            'shortName' => 'shortName',
            'subresourceOperations' => [],
            'swaggerContext' => ['description' => 'bar'],
            'validationGroups' => ['foo', 'bar'],
            'sunset' => 'Thu, 11 Oct 2018 00:00:00 +0200',
        ]);

        $this->assertSame('shortName', $resource->shortName);
        $this->assertSame('description', $resource->description);
        $this->assertSame('http://example.com/res', $resource->iri);
        $this->assertSame(['foo' => ['bar']], $resource->itemOperations);
        $this->assertSame(['bar' => ['foo']], $resource->collectionOperations);
        $this->assertSame([], $resource->subresourceOperations);
        $this->assertSame(['query' => ['normalization_context' => ['groups' => ['foo', 'bar']]]], $resource->graphql);
        $this->assertEquals([
            'access_control' => 'has_role("ROLE_FOO")',
            'access_control_message' => 'You are not foo.',
            'denormalization_context' => ['groups' => ['foo']],
            'fetch_partial' => true,
            'foo' => 'bar',
            'force_eager' => false,
            'formats' => ['foo', 'bar' => ['application/bar']],
            'filters' => ['foo', 'bar'],
            'input' => 'Foo',
            'maximum_items_per_page' => 42,
            'mercure' => '[\'foo\', object.owner]',
            'messenger' => true,
            'normalization_context' => ['groups' => ['bar']],
            'order' => ['foo', 'bar' => 'ASC'],
            'openapi_context' => ['description' => 'foo'],
            'output' => 'Bar',
            'pagination_client_enabled' => true,
            'pagination_client_items_per_page' => true,
            'pagination_client_partial' => true,
            'pagination_enabled' => true,
            'pagination_fetch_join_collection' => true,
            'pagination_items_per_page' => 42,
            'pagination_partial' => true,
            'route_prefix' => '/foo',
            'swagger_context' => ['description' => 'bar'],
            'validation_groups' => ['baz', 'qux'],
            'cache_headers' => ['max_age' => 0, 'shared_max_age' => 0],
            'sunset' => 'Thu, 11 Oct 2018 00:00:00 +0200',
        ], $resource->attributes);
    }

    public function testApiResourceAnnotation()
    {
        $reader = new AnnotationReader();
        /**
         * @var ApiResource
         */
        $resource = $reader->getClassAnnotation(new \ReflectionClass(AnnotatedClass::class), ApiResource::class);

        $this->assertSame('shortName', $resource->shortName);
        $this->assertSame('description', $resource->description);
        $this->assertSame('http://example.com/res', $resource->iri);
        $this->assertSame(['bar' => ['foo']], $resource->collectionOperations);
        $this->assertSame(['query' => ['normalization_context' => ['groups' => ['foo', 'bar']]]], $resource->graphql);
        $this->assertEquals([
            'foo' => 'bar',
            'route_prefix' => '/whatever',
            'access_control' => "has_role('ROLE_FOO')",
            'access_control_message' => 'You are not foo.',
            'cache_headers' => ['max_age' => 0, 'shared_max_age' => 0],
        ], $resource->attributes);
    }

    public function testConstructWithInvalidAttribute()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown property "invalidAttribute" on annotation "ApiPlatform\\Core\\Annotation\\ApiResource".');

        new ApiResource([
            'shortName' => 'shortName',
            'routePrefix' => '/foo',
            'invalidAttribute' => 'exception',
        ]);
    }
}
