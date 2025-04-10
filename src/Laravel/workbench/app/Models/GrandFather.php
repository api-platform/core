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

namespace Workbench\App\Models;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ApiResource]
#[ApiResource(
    uriTemplate: '/grand_sons/{id_grand_son}/grand_father',
    uriVariables: [
        'id_grand_son' => new Link(
            fromClass: GrandSon::class,
            fromProperty: 'grandfather',
            identifiers: ['id_grand_son']
        ),
    ],
    operations: [new Get()]
)]
#[ApiProperty(identifier: true, property: 'id_grand_father')]
class GrandFather extends Model
{
    protected $table = 'grand_fathers';
    protected $primaryKey = 'id_grand_father';
    protected $fillable = ['name', 'sons'];

    #[ApiProperty(genId: false, identifier: true)]
    private ?int $id_grand_father;

    private ?string $name = null;

    private ?Collection $sons = null;

    public function sons(): HasMany
    {
        return $this->hasMany(GrandSon::class, 'grand_father_id', 'id_grand_father');
    }
}
