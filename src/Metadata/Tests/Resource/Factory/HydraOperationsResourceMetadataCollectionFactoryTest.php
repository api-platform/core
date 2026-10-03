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

namespace ApiPlatform\Metadata\Tests\Resource\Factory;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\HydraOperation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\HydraOperationsResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\Dummy;
use PHPUnit\Framework\TestCase;

final class HydraOperationsResourceMetadataCollectionFactoryTest extends TestCase
{
    public function testResolvesMethodAndUriTemplateRegardlessOfTheFormatSuffix(): void
    {
        $resourceMetadataCollection = $this->create([
            new Get(uriTemplate: '/companies{._format}', name: 'get', hydraOperations: [new HydraOperation(method: 'delete', uriTemplate: '/companies')]),
            new Delete(uriTemplate: '/companies{._format}', name: 'delete'),
        ]);

        $this->assertEquals([new HydraOperation(method: 'DELETE', uriTemplate: '/companies{._format}', name: 'delete')], $this->getHydraOperations($resourceMetadataCollection, 'get'));
    }

    public function testExactUriTemplateWinsOverTheFormatAgnosticMatch(): void
    {
        $resourceMetadataCollection = $this->create([
            new Get(uriTemplate: '/companies', name: 'get', hydraOperations: [new HydraOperation(method: 'DELETE', uriTemplate: '/companies')]),
            new Delete(uriTemplate: '/companies{._format}', name: 'lenient'),
            new Delete(uriTemplate: '/companies', name: 'exact'),
        ]);

        $this->assertEquals([new HydraOperation(method: 'DELETE', uriTemplate: '/companies', name: 'exact')], $this->getHydraOperations($resourceMetadataCollection, 'get'));
    }

    public function testResolvesNameAcrossTheResourcesOfTheClassAndKeepsItsOwnSecurity(): void
    {
        $resourceMetadataCollection = $this->create(
            [new Get(uriTemplate: '/companies/{id}{._format}', name: 'get', hydraOperations: [new HydraOperation(name: 'archive', security: "is_granted('ROLE_ADMIN')")])],
            [new Patch(uriTemplate: '/companies/{id}/archive{._format}', name: 'archive', security: "is_granted('ROLE_USER')")],
        );

        $this->assertEquals([new HydraOperation(method: 'PATCH', uriTemplate: '/companies/{id}/archive{._format}', name: 'archive', security: "is_granted('ROLE_ADMIN')")], $this->getHydraOperations($resourceMetadataCollection, 'get'));
    }

    public function testMethodDefaultsToTheUriTemplateOfTheDeclaringOperation(): void
    {
        $resourceMetadataCollection = $this->create([
            new GetCollection(uriTemplate: '/companies{._format}', name: 'get_collection', hydraOperations: [new HydraOperation(method: 'POST')]),
            new Post(uriTemplate: '/admin/companies{._format}', name: 'admin_post'),
            new Post(uriTemplate: '/companies{._format}', name: 'post'),
        ]);

        $this->assertEquals([new HydraOperation(method: 'POST', uriTemplate: '/companies{._format}', name: 'post')], $this->getHydraOperations($resourceMetadataCollection, 'get_collection'));
    }

    public function testLeavesUnsetAndDisabledHydraOperationsUntouched(): void
    {
        $resourceMetadataCollection = $this->create([
            new Get(uriTemplate: '/companies/{id}{._format}', name: 'get'),
            new GetCollection(uriTemplate: '/companies{._format}', name: 'get_collection', hydraOperations: false),
        ]);

        $this->assertNull($this->getHydraOperations($resourceMetadataCollection, 'get'));
        $this->assertFalse($this->getHydraOperations($resourceMetadataCollection, 'get_collection'));
    }

    public function testThrowsWhenTheReferencedOperationIsNotDeclared(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The Hydra operation "DELETE /companies/{id}" referenced by the operation "get" is not declared on the resource "'.Dummy::class.'".');

        $this->create([
            new Get(uriTemplate: '/companies{._format}', name: 'get', hydraOperations: [new HydraOperation(method: 'DELETE', uriTemplate: '/companies/{id}')]),
            new Delete(uriTemplate: '/companies{._format}', name: 'delete'),
        ]);
    }

    /**
     * @param list<HttpOperation> ...$operations the operations of each resource
     */
    private function create(array ...$operations): ResourceMetadataCollection
    {
        $decorated = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->method('create')->willReturn(new ResourceMetadataCollection(Dummy::class, array_map(
            static fn (array $resourceOperations): ApiResource => new ApiResource(class: Dummy::class, operations: array_map(static fn (HttpOperation $operation): HttpOperation => $operation->withClass(Dummy::class), $resourceOperations)),
            $operations,
        )));

        return (new HydraOperationsResourceMetadataCollectionFactory($decorated))->create(Dummy::class);
    }

    /**
     * @return list<HydraOperation>|false|null
     */
    private function getHydraOperations(ResourceMetadataCollection $resourceMetadataCollection, string $operationName): array|false|null
    {
        $operation = $resourceMetadataCollection->getOperation($operationName);
        $this->assertInstanceOf(HttpOperation::class, $operation);

        return $operation->getHydraOperations();
    }
}
