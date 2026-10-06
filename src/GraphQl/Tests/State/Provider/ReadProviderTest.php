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

namespace ApiPlatform\GraphQl\Tests\State\Provider;

use ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\GraphQl\State\Provider\ReadProvider;
use ApiPlatform\Metadata\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\GraphQl\SubscriptionCollection;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\State\ProviderInterface;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;

class ReadProviderTest extends TestCase
{
    public function testProvide(): void
    {
        $context = ['args' => ['id' => '/dummy/1']];
        $operation = new Query(class: \stdClass::class);
        $decorated = $this->createMock(ProviderInterface::class);
        $iriConverter = $this->createMock(IriConverterInterface::class);
        $iriConverter->expects($this->once())->method('getResourceFromIri')->with('/dummy/1');
        $serializerContextBuilder = $this->createMock(SerializerContextBuilderInterface::class);
        $provider = new ReadProvider($decorated, $iriConverter, $serializerContextBuilder, '.');
        $provider->provide($operation, [], $context);
    }

    /**
     * Tests that provider returns null if resource is not found.
     *
     * @see https://github.com/api-platform/core/issues/6072
     */
    public function testProvideNotExistedResource(): void
    {
        $context = ['args' => ['id' => '/dummy/1']];
        $operation = new Query(class: \stdClass::class);
        $decorated = $this->createMock(ProviderInterface::class);
        $iriConverter = $this->createMock(IriConverterInterface::class);
        $iriConverter->expects($this->once())->method('getResourceFromIri')->with('/dummy/1');
        $iriConverter->method('getResourceFromIri')->willThrowException(new ItemNotFoundException());
        $serializerContextBuilder = $this->createMock(SerializerContextBuilderInterface::class);
        $provider = new ReadProvider($decorated, $iriConverter, $serializerContextBuilder, '.');
        $result = $provider->provide($operation, [], $context);

        $this->assertNull($result);
    }

    public function testProvideCollection(): void
    {
        $info = $this->createMock(ResolveInfo::class);
        $info->fieldName = '';
        $context = ['root_class' => \stdClass::class, 'source' => [], 'info' => $info, 'filters' => []];
        $operation = new QueryCollection(class: \stdClass::class);
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->expects($this->once())->method('provide')->with($operation, [], ['a'] + $context);
        $iriConverter = $this->createMock(IriConverterInterface::class);
        $serializerContextBuilder = $this->createMock(SerializerContextBuilderInterface::class);
        $serializerContextBuilder->expects($this->once())->method('create')->willReturn(['a']);
        $provider = new ReadProvider($decorated, $iriConverter, $serializerContextBuilder, '.');
        $provider->provide($operation, [], $context);
    }

    public function testCollectionSubscriptionReadsAndClonesTheEnrollmentItem(): void
    {
        $item = (object) ['tenant' => 'tenant-a'];
        $operation = new SubscriptionCollection(class: \stdClass::class);
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->expects($this->never())->method('provide');
        $iriConverter = $this->createMock(IriConverterInterface::class);
        $iriConverter->expects($this->once())->method('getResourceFromIri')->with('/dummy/1')->willReturn($item);
        $provider = new ReadProvider($decorated, $iriConverter, null, '.');
        /** @var array<string, mixed> $graphQlContext */
        $graphQlContext = [];

        $this->assertSame($item, $provider->provide($operation, [], [
            'args' => ['input' => ['id' => '/dummy/1']],
            'graphql_context' => &$graphQlContext,
        ]));
        $this->assertArrayHasKey('previous_object', $graphQlContext);
        $this->assertEquals($item, $graphQlContext['previous_object']);
        $this->assertNotSame($item, $graphQlContext['previous_object']);
    }

    public function testCollectionSubscriptionRejectsAnItemOfAnotherResourceClass(): void
    {
        $operation = new SubscriptionCollection(class: \stdClass::class, shortName: 'Dummy');
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->expects($this->never())->method('provide');
        $iriConverter = $this->createMock(IriConverterInterface::class);
        $iriConverter->expects($this->once())->method('getResourceFromIri')->willReturn(new \ArrayObject());
        $provider = new ReadProvider($decorated, $iriConverter, null, '.');

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('did not match expected type "Dummy"');
        $provider->provide($operation, [], ['args' => ['input' => ['id' => '/other/1']]]);
    }
}
