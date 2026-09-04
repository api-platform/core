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

namespace ApiPlatform\Doctrine\Orm\Tests\Metadata\Resource;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\FilterInterface;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Metadata\Resource\Factory\AttributesResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ParameterResourceMetadataCollectionFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class UnwiredLegacyFilterParameterTest extends TestCase
{
    public function testUnwiredRegistryAwareFilterIsNotLogged(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('alert');
        $logger->expects($this->never())->method('debug');

        $this->createFactory($logger)->create(ResourceWithInlineDateFilter::class);
    }

    public function testFilterFailureUnrelatedToTheManagerRegistryStillAlerts(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('alert');
        $logger->expects($this->never())->method('debug');

        $this->createFactory($logger)->create(ResourceWithThrowingFilter::class);
    }

    private function createFactory(LoggerInterface $logger): ParameterResourceMetadataCollectionFactory
    {
        $propertyNameCollectionFactory = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory->method('create')->willReturn(new PropertyNameCollection(['id', 'updatedAt']));

        $propertyMetadataFactory = $this->createStub(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->method('create')->willReturn(new ApiProperty(readable: true));

        return new ParameterResourceMetadataCollectionFactory(
            $propertyNameCollectionFactory,
            $propertyMetadataFactory,
            new AttributesResourceMetadataCollectionFactory(),
            null,
            null,
            $logger,
        );
    }
}

final class ThrowingFilter implements FilterInterface
{
    public function getDescription(string $resourceClass): array
    {
        throw new RuntimeException('Something unexpected happened.');
    }
}

#[ApiResource(operations: [
    new GetCollection(parameters: ['updatedAt' => new QueryParameter(filter: new DateFilter())]),
])]
final class ResourceWithInlineDateFilter
{
    public $id;
    public $updatedAt;
}

#[ApiResource(operations: [
    new GetCollection(parameters: ['updatedAt' => new QueryParameter(filter: new ThrowingFilter())]),
])]
final class ResourceWithThrowingFilter
{
    public $id;
    public $updatedAt;
}
