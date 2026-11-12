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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document\IriFilterNestedScalarEnum;

use ApiPlatform\Doctrine\Odm\Filter\IriFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
#[ApiResource(
    shortName: 'IriFilterNestedScalarEnumTournamentPlayer',
    operations: [
        new GetCollection(
            normalizationContext: ['hydra_prefix' => false],
            parameters: [
                'tournamentPlayMode' => new QueryParameter(filter: new IriFilter(), property: 'tournament.playMode'),
            ],
        ),
        new Get(),
    ]
)]
class TournamentPlayer
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;

    #[ODM\Field(type: 'string')]
    public string $name;

    #[ODM\ReferenceOne(targetDocument: Tournament::class, storeAs: 'id')]
    public ?Tournament $tournament = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
