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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Resource;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Models\PatchDummyRelation as PatchDummyRelationModel;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource(
 *     dataModel=PatchDummyRelationModel::class,
 *     attributes={
 *         "normalization_context"={"groups"={"chicago"}},
 *         "denormalization_context"={"groups"={"chicago"}},
 *     },
 *     itemOperations={
 *         "get",
 *         "patch"={"input_formats"={"json"={"application/merge-patch+json"}, "jsonapi"}}
 *     }
 * )
 */
class PatchDummyRelation
{
    /**
     * @ApiProperty(identifier=true)
     */
    public $id;

    /**
     * @var RelatedDummy
     *
     * @Groups({"chicago"})
     */
    protected $related;

    public function getRelated()
    {
        return $this->related;
    }

    public function setRelated(RelatedDummy $relatedDummy)
    {
        $this->related = $relatedDummy;
    }
}
