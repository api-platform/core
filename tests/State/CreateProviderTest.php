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

namespace ApiPlatform\Tests\State;

use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\State\CreateProvider;
use ApiPlatform\State\Exception\ProviderNotFoundException;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Company;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyResourceWithComplexConstructor;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Employee;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CreateProviderTest extends TestCase
{
    use ProphecyTrait;

    public function testProvide(): void
    {
        $decorated = $this->prophesize(ProviderInterface::class);
        $resourceMetadataCollectionFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $uriVariables = ['company' => 1];
        $link = new Link(identifiers: ['id'], fromClass: Company::class, parameterName: 'company');
        $operation = new Post(class: Employee::class, uriTemplate: '/companies/{company}/employees', uriVariables: ['company' => $link]);
        $parentOperation = new Get(uriVariables: ['id' => $link], class: Company::class);

        $resourceMetadataCollectionFactory
            ->create(Company::class)
            ->shouldBeCalledOnce()
            ->willReturn(
                new ResourceMetadataCollection(Company::class, [
                    new ApiResource(operations: [$parentOperation]),
                ])
            );
        $decorated->provide($parentOperation, $uriVariables, [])->shouldBeCalled()->willReturn(new Company());

        $createProvider = new CreateProvider($decorated->reveal(), $resourceMetadataCollectionFactory->reveal());
        $createProvider->provide($operation, $uriVariables);
    }

    public function testProvideParentNotFound(): void
    {
        $decorated = $this->prophesize(ProviderInterface::class);
        $resourceMetadataCollectionFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $uriVariables = ['company' => 1];
        $link = new Link(identifiers: ['id'], fromClass: Company::class, parameterName: 'company');
        $operation = new Post(class: Employee::class, uriTemplate: '/companies/{company}/employees', uriVariables: ['company' => $link]);
        $parentOperation = new Get(uriVariables: ['id' => $link], class: Company::class);

        $resourceMetadataCollectionFactory
            ->create(Company::class)
            ->shouldBeCalledOnce()
            ->willReturn(
                new ResourceMetadataCollection(Company::class, [
                    new ApiResource(operations: [$parentOperation]),
                ])
            );
        $decorated->provide($parentOperation, $uriVariables, [])->shouldBeCalled()->willReturn(null);

        $this->expectException(NotFoundHttpException::class);

        $createProvider = new CreateProvider($decorated->reveal(), $resourceMetadataCollectionFactory->reveal());
        $createProvider->provide($operation, $uriVariables);
    }

    public function testProvideParentProviderNotFound(): void
    {
        $decorated = $this->prophesize(ProviderInterface::class);
        $resourceMetadataCollectionFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $uriVariables = ['company' => 1];
        $link = new Link(identifiers: ['id'], fromClass: Company::class, parameterName: 'company');
        $operation = new Post(class: Employee::class, uriTemplate: '/companies/{company}/employees', uriVariables: ['company' => $link]);
        $parentOperation = new Get(uriVariables: ['id' => $link], class: Company::class);

        $resourceMetadataCollectionFactory
            ->create(Company::class)
            ->shouldBeCalledOnce()
            ->willReturn(
                new ResourceMetadataCollection(Company::class, [
                    new ApiResource(operations: [$parentOperation]),
                ])
            );
        $decorated->provide($parentOperation, $uriVariables, [])->shouldBeCalled()->willThrow(ProviderNotFoundException::class);

        $this->expectException(NotFoundHttpException::class);

        $createProvider = new CreateProvider($decorated->reveal(), $resourceMetadataCollectionFactory->reveal());
        $createProvider->provide($operation, $uriVariables);
    }

    public function testProvideWithInvalidParentResourceClass(): void
    {
        $decorated = $this->prophesize(ProviderInterface::class);
        $resourceMetadataCollectionFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $uriVariables = ['company' => 1];
        $link = new Link(identifiers: ['id'], fromClass: Company::class, parameterName: 'company');
        $operation = new Post(
            class: Employee::class,
            uriTemplate: '/companies/{company}/employees',
            uriVariables: ['company' => $link]
        );
        $parentOperation = new Get(uriVariables: ['id' => $link], class: Company::class);

        $resourceMetadataCollectionFactory->create(Company::class)->shouldBeCalledOnce()->willThrow(ResourceClassNotFoundException::class);
        $decorated->provide($parentOperation, $uriVariables, [])->shouldNotBeCalled();

        $this->expectException(ResourceClassNotFoundException::class);

        $createProvider = new CreateProvider($decorated->reveal(), $resourceMetadataCollectionFactory->reveal());
        $createProvider->provide($operation, $uriVariables);
    }

    public function testProvideWithParentEmptyOperations(): void
    {
        $decorated = $this->prophesize(ProviderInterface::class);
        $resourceMetadataCollectionFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $uriVariables = ['company' => 1];
        $link = new Link(identifiers: ['id'], fromClass: Company::class, parameterName: 'company');
        $operation = new Post(
            class: Employee::class,
            uriTemplate: '/companies/{company}/employees',
            uriVariables: ['company' => $link]
        );
        $parentOperation = new Get(uriVariables: ['id' => $link], class: Company::class);

        $resourceMetadataCollectionFactory
            ->create(Company::class)
            ->shouldBeCalledOnce()
            ->willReturn(
                new ResourceMetadataCollection(Company::class, [
                    new ApiResource(),
                ])
            );
        $decorated->provide($parentOperation, $uriVariables, [])->shouldNotBeCalled();

        $this->expectException(OperationNotFoundException::class);

        $createProvider = new CreateProvider($decorated->reveal(), $resourceMetadataCollectionFactory->reveal());
        $createProvider->provide($operation, $uriVariables);
    }

    public function testProvideWithParentUriTemplate(): void
    {
        $decorated = $this->prophesize(ProviderInterface::class);
        $resourceMetadataCollectionFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $uriVariables = ['company' => 1];
        $link = new Link(identifiers: ['id'], fromClass: Company::class, parameterName: 'company');
        $operation = new Post(
            class: Employee::class,
            uriTemplate: '/companies/{company}/employees',
            uriVariables: ['company' => $link],
            extraProperties: ['parent_uri_template' => '/companies/{id}']
        );
        $parentOperation = new Get(uriTemplate: '/companies/{id}', uriVariables: ['id' => $link], class: Company::class, priority: 1);

        $resourceMetadataCollectionFactory
            ->create(Company::class)
            ->shouldBeCalledOnce()
            ->willReturn(
                new ResourceMetadataCollection(Company::class, [
                    new ApiResource(operations: [
                        new Get(),
                        $parentOperation,
                    ]),
                ])
            );
        $decorated->provide($parentOperation, $uriVariables, [])->shouldBeCalled()->willReturn(new Company());

        $createProvider = new CreateProvider($decorated->reveal(), $resourceMetadataCollectionFactory->reveal());
        $createProvider->provide($operation, $uriVariables);
    }

    public function testProvideFailsProperlyOnComplexConstructor(): void
    {
        $decorated = $this->prophesize(ProviderInterface::class);
        $resourceMetadataCollectionFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $uriVariables = ['company' => 1];
        $link = new Link(identifiers: ['id'], fromClass: Company::class, parameterName: 'company');
        $operation = new Post(class: DummyResourceWithComplexConstructor::class, uriTemplate: '/companies/{company}/employees', uriVariables: ['company' => $link]);
        $parentOperation = new Get(uriVariables: ['id' => $link], class: Company::class);

        $resourceMetadataCollectionFactory
            ->create(Company::class)
            ->shouldBeCalledOnce()
            ->willReturn(
                new ResourceMetadataCollection(Company::class, [
                    new ApiResource(operations: [$parentOperation]),
                ])
            );
        $decorated->provide($parentOperation, $uriVariables, [])->shouldBeCalled()->willReturn(new Company());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('An error occurred while trying to create an instance of the "ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyResourceWithComplexConstructor" resource. Consider writing your own "ApiPlatform\State\ProviderInterface" implementation and setting it as `provider` on your operation instead.');

        $createProvider = new CreateProvider($decorated->reveal(), $resourceMetadataCollectionFactory->reveal());
        $createProvider->provide($operation, $uriVariables);
    }

    public function testSkipWhenController(): void
    {
        $decorated = $this->prophesize(ProviderInterface::class);
        $resourceMetadataCollectionFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $uriVariables = ['company' => 1];
        $operation = new Post(class: Employee::class, uriTemplate: '/companies/{company}/employees', controller: 'test');

        $resourceMetadataCollectionFactory->create(Company::class)->shouldNotBeCalled();
        $decorated->provide($operation, $uriVariables, [])->shouldBeCalled()->willReturn(new Employee());

        $createProvider = new CreateProvider($decorated->reveal(), $resourceMetadataCollectionFactory->reveal());
        $createProvider->provide($operation, $uriVariables);
    }
}
