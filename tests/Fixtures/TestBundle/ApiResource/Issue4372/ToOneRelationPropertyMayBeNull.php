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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: self::ROUTE.'{._format}',
        ),
        new Get(
            uriTemplate: self::ITEM_ROUTE.'{._format}',
            provider: [self::class, 'provide']
        ),
    ],
)]
class ToOneRelationPropertyMayBeNull
{
    public const ROUTE = '/my-route';
    public const ITEM_ROUTE = self::ROUTE.'/{id}';
    public const ENTITY_ID = 1;

    /** @noinspection PhpPropertyOnlyWrittenInspection */
    #[ApiProperty(identifier: true)]
    private ?int $id = null;

    #[ApiProperty]
    public ?RelatedEntity $relatedEntity = null;

    #[ApiProperty]
    public ?RelatedEntity $relatedEntity2 = null;

    #[ApiProperty]
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
        $toOneRelationPropertyMayBeNull->collection = new ArrayCollection([$relatedEntity1, $relatedEntity2]);

        return $toOneRelationPropertyMayBeNull;
    }
}
