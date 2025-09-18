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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue4372;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Serializer\AbstractItemNormalizer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: self::ROUTE.'{._format}',
        ),
        new Get(
            uriTemplate: self::ITEM_ROUTE.'{._format}',
            normalizationContext: [
                AbstractItemNormalizer::SKIP_NULL_TO_ONE_RELATIONS => false,
                'groups' => ['default', 'read'],
            ],
            provider: [self::class, 'provide']
        ),
        new Get(
            uriTemplate: self::ITEM_SKIP_NULL_TO_ONE_RELATION_ROUTE.'{._format}',
            normalizationContext: [
                'groups' => ['default', 'read'],
            ],
            provider: [self::class, 'provide']
        ),
    ],
)]
class ToOneRelationPropertyMayBeNull
{
    public const ROUTE = '/my-route';
    public const ITEM_ROUTE = self::ROUTE.'/{id}';
    public const SKIP_NULL_TO_ONE_RELATION_ROUTE = '/skip-null-relation-route';
    public const ITEM_SKIP_NULL_TO_ONE_RELATION_ROUTE = self::SKIP_NULL_TO_ONE_RELATION_ROUTE.'/{id}';
    public const ENTITY_ID = 1;

    #[ApiProperty(identifier: true)]
    #[Groups(['read'])]
    public ?int $id = null;

    #[ApiProperty]
    public ?RelatedEntity $relatedEntity = null;

    #[ApiProperty(readableLink: true)]
    #[Groups(['read'])]
    public ?RelatedEntity $relatedEmbeddedEntity = null;

    #[ApiProperty]
    public ?RelatedEntity $relatedEntity2 = null;

    #[ApiProperty(readableLink: true)]
    #[Groups(['read'])]
    public ?RelatedEntity $relatedEmbeddedEntity2 = null;

    #[ApiProperty]
    #[Groups(['read'])]
    public Collection $collection;

    public function __construct()
    {
        $this->collection = new ArrayCollection();
    }

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): ?self
    {
        $relatedEntity1 = new RelatedEntity();
        $relatedEntity1->id = 1;
        $relatedEntity2 = new RelatedEntity();
        $relatedEntity2->id = 2;

        $toOneRelationPropertyMayBeNull = new self();
        $toOneRelationPropertyMayBeNull->id = self::ENTITY_ID;
        $toOneRelationPropertyMayBeNull->relatedEntity2 = $relatedEntity1;
        $toOneRelationPropertyMayBeNull->relatedEmbeddedEntity2 = $relatedEntity1;
        $toOneRelationPropertyMayBeNull->collection = new ArrayCollection([$relatedEntity1, $relatedEntity2]);

        return $toOneRelationPropertyMayBeNull;
    }
}
