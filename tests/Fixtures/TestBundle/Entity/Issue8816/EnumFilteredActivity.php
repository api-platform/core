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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8816;

use ApiPlatform\Doctrine\Orm\Filter\ExactFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new Get(uriTemplate: 'enum_filtered_activities/{id}{._format}'),
        new GetCollection(
            uriTemplate: 'enum_filtered_activities{._format}',
            parameters: [
                'type' => new QueryParameter(filter: new ExactFilter(), property: 'type', enumClass: ActivityType::class),
                'level' => new QueryParameter(filter: new ExactFilter(), property: 'level', enumClass: ActivityLevel::class),
            ],
        ),
    ],
    graphQlOperations: [
        new Query(),
        new QueryCollection(
            parameters: [
                'type' => new QueryParameter(filter: new ExactFilter(), property: 'type', enumClass: ActivityType::class),
                'level' => new QueryParameter(filter: new ExactFilter(), property: 'level', enumClass: ActivityLevel::class),
            ],
        ),
    ],
)]
#[ORM\Entity]
class EnumFilteredActivity
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(enumType: ActivityType::class)]
    private ActivityType $type;

    #[ORM\Column(enumType: ActivityLevel::class)]
    private ActivityLevel $level;

    public function __construct(ActivityType $type = ActivityType::Meeting, ActivityLevel $level = ActivityLevel::Beginner)
    {
        $this->type = $type;
        $this->level = $level;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ActivityType
    {
        return $this->type;
    }

    public function getLevel(): ActivityLevel
    {
        return $this->level;
    }
}
