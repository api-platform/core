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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5736Aerendir;

use ApiPlatform\Metadata as API;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[ORM\Entity]
#[ORM\Table(name: 'issue5736_employees')]
#[API\ApiResource(
    normalizationContext: [
        AbstractNormalizer::GROUPS => [self::GROUP_NOR_READ],
    ],
    denormalizationContext: [
        AbstractNormalizer::GROUPS => [self::GROUP_DENOR_WRITE],
    ],
    operations: [
        new API\GetCollection(
            uriTemplate: self::API_ENDPOINT,
            uriVariables: [
                Company::API_ID_PLACEHOLDER => new API\Link(fromClass: Company::class, toProperty: 'company', identifiers: ['id']),
                Team::API_ID_PLACEHOLDER    => new API\Link(fromClass: Team::class, toProperty: 'team', identifiers: ['id']),
            ],
        ),
        new API\Get(
            uriTemplate: self::API_RESOURCE,
            uriVariables: [
                Company::API_ID_PLACEHOLDER => new API\Link(fromClass: Company::class, toProperty: 'company', identifiers: ['id']),
                Team::API_ID_PLACEHOLDER    => new API\Link(fromClass: Team::class, toProperty: 'team', identifiers: ['id']),
                Employee::API_ID_PLACEHOLDER    => new API\Link(fromClass: self::class, identifiers: ['id']),
            ],
        ),
    ],
)]
class Employee
{
    public const API_ID_PLACEHOLDER = 'issue5736_employee';
    public const API_ENDPOINT = Team::API_RESOURCE . '/employees';
    public const API_RESOURCE = self::API_ENDPOINT . '/{' . self::API_ID_PLACEHOLDER . '}';
    public const GROUP_NOR_READ    = 'employee:read';
    public const GROUP_DENOR_WRITE = 'employee:write';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    #[Groups([self::GROUP_NOR_READ, Team::GROUP_NOR_READ])]
    private $id;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'employees')]
    #[ORM\JoinColumn(name: 'company', nullable: false)]
    #[Groups([self::GROUP_NOR_READ])]
    private Company $company;

    #[ORM\ManyToOne(targetEntity: Team::class, inversedBy: 'employees')]
    #[ORM\JoinColumn(name: 'team', nullable: false)]
    #[Groups([self::GROUP_NOR_READ, Team::GROUP_NOR_READ])]
    private Team $team;

    #[ORM\Column]
    #[Groups([self::GROUP_NOR_READ])]
    private string $name;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    public function getTeam() : Team
    {
        return $this->team;
    }

    public function setTeam(Team $team) : void
    {
        $this->team = $team;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : void
    {
        $this->name = $name;
    }
}
