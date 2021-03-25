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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Models;

use ApiPlatform\Core\Annotation\ApiResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Relation Embedder.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource(
 *     attributes={
 *         "normalization_context"={"groups"={"barcelona"}},
 *         "denormalization_context"={"groups"={"chicago"}},
 *         "hydra_context"={"@type"="hydra:Operation", "hydra:title"="A custom operation", "returns"="xmls:string"}
 *     },
 *     itemOperations={
 *         "get",
 *         "put"={},
 *         "delete",
 *         "custom_get"={"route_name"="relation_embedded.custom_get", "method"="GET"},
 *         "custom1"={"path"="/api/custom-call/{id}", "method"="GET"},
 *         "custom2"={"path"="/api/custom-call/{id}", "method"="PUT"},
 *     },
 *     properties={
 *         "paris"={"groups"={"chicago"}},
 *         "krondstadt"={"groups"={"barcelona", "chicago"}},
 *         "anotherRelated"={"relation"=RelatedDummy::class, "groups"={"barcelona", "chicago"}},
 *         "related"={"relation"=RelatedDummy::class, "groups"={"barcelona", "chicago"}}
 *     }
 * )
 */
class RelationEmbedder extends Model
{
    public $timestamps = false;

    protected $attributes = [
        'paris' => 'Paris',
        'krondstadt' => 'Krondstadt',
    ];

    public function anotherRelated(): BelongsTo
    {
        return $this->belongsTo(RelatedDummy::class);
    }

    public function related(): BelongsTo
    {
        return $this->belongsTo(RelatedDummy::class);
    }
}
