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

namespace ApiPlatform\GraphQl\Tests\Resolver\Factory;

use ApiPlatform\GraphQl\Resolver\Factory\ResolverFactory;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;

class ResolverFactoryTest extends TestCase
{
    /**
     * @dataProvider graphQlQueries
     */
    public function testGraphQlResolver(?string $resourceClass = null, ?string $rootClass = null, ?Operation $operation = null, ?Operation $providedOperation = null, ?Operation $processedOperation = null): void
    {
        $returnValue = new \stdClass();
        $body = new \stdClass();
        $context = $this->logicalAnd(
            $this->arrayHasKey('source'), $this->arrayHasKey('args'), $this->arrayHasKey('graphql_context'), $this->arrayHasKey('info'), $this->arrayHasKey('root_class')
        );
        $provider = $this->createMock(ProviderInterface::class);
        $provider->expects($this->once())->method('provide')->with($providedOperation ?: $operation, [], $context)->willReturn($body);
        $processor = $this->createMock(ProcessorInterface::class);
        $processor->expects($this->once())->method('process')->with($body, $processedOperation ?: $operation, [], $context)->willReturn($returnValue);
        $resolveInfo = $this->createMock(ResolveInfo::class);
        $resolveInfo->fieldName = 'test';

        $resolverFactory = new ResolverFactory($provider, $processor);
        $this->assertEquals($resolverFactory->__invoke($resourceClass, $rootClass, $operation)(['test' => null], [], [], $resolveInfo), $returnValue);
    }

    public static function graphQlQueries(): array
    {
        return [
            ['Dummy', 'Dummy', new Query()],
            ['Dummy', 'Dummy', new Mutation(), (new Mutation())->withValidate(true), (new Mutation())->withValidate(true)->withWrite(true)],
        ];
    }
}
