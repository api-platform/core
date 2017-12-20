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

namespace ApiPlatform\Core\Tests\Graphql\Resolver;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\Graphql\Resolver\ItemResolverFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ItemResolverFactoryTest extends TestCase
{
    public function testCreateItemResolverNoItem()
    {
        $resolverFactory = $this->createItemResolverFactory(null);
        $resolver = $resolverFactory(RelatedDummy::class, Dummy::class, 'operationName');

        $resolveInfo = new ResolveInfo([]);
        $resolveInfo->fieldName = 'name';
        $resolveInfo->fieldNodes = [];

        $this->assertNull($resolver(null, ['id' => '/related_dummies/3'], null, $resolveInfo));
    }

    public function testCreateItemResolver()
    {
        $resolverFactory = $this->createItemResolverFactory(new RelatedDummy());
        $resolver = $resolverFactory(RelatedDummy::class, Dummy::class, 'operationName');

        $resolveInfo = new ResolveInfo([]);
        $resolveInfo->fieldName = 'name';
        $resolveInfo->fieldNodes = [];

        $this->assertEquals('normalizedItem', $resolver(null, ['id' => '/related_dummies/3'], null, $resolveInfo));
    }

    /**
     * @dataProvider subresourceProvider
     */
    public function testCreateSubresourceItemResolver($normalizedSubresource)
    {
        $resolverFactory = $this->createItemResolverFactory(new Dummy());
        $resolver = $resolverFactory(RelatedDummy::class, Dummy::class, 'operationName');

        $resolveInfo = new ResolveInfo([]);
        $resolveInfo->fieldName = 'relatedDummy';
        $resolveInfo->fieldNodes = [];

        $this->assertEquals($normalizedSubresource, $resolver(['relatedDummy' => $normalizedSubresource], [], null, $resolveInfo));
    }

    public function subresourceProvider(): array
    {
        return [
            ['/related_dummies/3'],
            [null],
        ];
    }

    private function createItemResolverFactory($item): ItemResolverFactory
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $getItemFromIri = $iriConverterProphecy->getItemFromIri('/related_dummies/3');
        null === $item ? $getItemFromIri->willThrow(new ItemNotFoundException()) : $getItemFromIri->willReturn($item);

        $normalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $normalizerProphecy->normalize($item, Argument::cetera())->willReturn('normalizedItem');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('Dummy', null, null, null, null, ['normalization_context' => ['groups' => ['foo']]]));
        $resourceMetadataFactoryProphecy->create(RelatedDummy::class)->willReturn(new ResourceMetadata('RelatedDummy', null, null, null, null, ['normalization_context' => ['groups' => ['foo']]]));

        return new ItemResolverFactory(
            $iriConverterProphecy->reveal(),
            $normalizerProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal()
        );
    }
}
