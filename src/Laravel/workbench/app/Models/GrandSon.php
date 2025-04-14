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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ApiResource]
#[ApiResource(
    uriTemplate: '/grand_fathers/{id_grand_father}/grand_sons',
    uriVariables: [
        'id_grand_father' => new Link(
            fromClass: GrandFather::class,
            fromProperty: 'sons',
            identifiers: ['id_grand_father']
        ),
    ],
    operations: [new GetCollection()]
)]
#[ApiProperty(identifier: true, property: 'id_grand_son')]
class GrandSon extends Model
{
    protected $table = 'grand_sons';
    protected $primaryKey = 'id_grand_son';
    protected $fillable = ['name', 'grand_father_id', 'grandfather'];

    #[ApiProperty(genId: false, identifier: true)]
    private ?int $id_grand_son;

    private ?string $name = null;

    private ?GrandFather $grandfather = null;

    public function grandfather(): BelongsTo
    {
        return $this->belongsTo(GrandFather::class, 'grand_father_id', 'id_grand_father');
    }
}
