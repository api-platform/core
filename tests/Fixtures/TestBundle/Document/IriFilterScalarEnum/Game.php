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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document\IriFilterScalarEnum;

use ApiPlatform\Doctrine\Odm\Filter\IriFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Tests\Fixtures\TestBundle\Enum\GamePlayMode;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
#[ApiResource(
    shortName: 'IriFilterScalarEnumGame',
    operations: [
        new GetCollection(
            normalizationContext: ['hydra_prefix' => false],
            parameters: [
                'playMode' => new QueryParameter(filter: new IriFilter()),
            ],
        ),
        new Get(),
    ]
)]
class Game
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;

    #[ODM\Field(type: 'string')]
    public string $name;

    // Scalar field backed by an enum that is itself exposed as an API resource:
    // the enum is never a Doctrine reference, so IriFilter must resolve the IRI
    // to the enum case and match the scalar field against its backing value.
    #[ODM\Field(type: 'string', enumType: GamePlayMode::class)]
    public GamePlayMode $playMode = GamePlayMode::SINGLE_PLAYER;

    public function getId(): ?int
    {
        return $this->id;
    }
}
