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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[ORM\Entity]
#[ORM\Table(name: 'issue5736_companies')]
#[API\ApiResource(
    normalizationContext: [
        AbstractNormalizer::GROUPS => [self::GROUP_NOR_READ],
    ],
    denormalizationContext: [
        AbstractNormalizer::GROUPS => [self::GROUP_DENOR_WRITE],
    ],
    operations: [
        new API\GetCollection(
            uriTemplate: Company::API_ENDPOINT,
        ),
        new API\Get(
            uriTemplate: Company::API_RESOURCE,
            uriVariables: [
                Company::API_ID_PLACEHOLDER => new API\Link(fromClass: self::class, identifiers: ['id']),
            ],
        ),
        new API\Post(
            read: false,
            uriTemplate: Company::API_ENDPOINT,
        ),
        new API\Put(
            uriTemplate: Company::API_RESOURCE,
            uriVariables: [
                Company::API_ID_PLACEHOLDER => new API\Link(fromClass: self::class, identifiers: ['id']),
            ],
        ),
    ],
)]
class Company
{
    public const API_ID_PLACEHOLDER = 'issue5736_company';
    public const API_ENDPOINT = 'issue5736_companies';
    public const API_RESOURCE = '/' . self::API_ENDPOINT . '/{' . self::API_ID_PLACEHOLDER . '}';
    public const GROUP_NOR_READ    = 'company:read';
    public const GROUP_DENOR_WRITE = 'company:write';

    #[ORM\Column(type: 'integer', nullable: true)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[Groups([self::GROUP_NOR_READ])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups([self::GROUP_NOR_READ, self::GROUP_DENOR_WRITE])]
    private string $name;

    /** @var Collection<Team>  */
    #[ORM\OneToMany(targetEntity: Team::class, mappedBy: 'company')]
    private Collection $teams;

    /** @var Collection<Employee>  */
    #[ORM\OneToMany(targetEntity: Team::class, mappedBy: 'company')]
    private Collection $employees;

    public function __construct()
    {
        $this->teams     = new ArrayCollection();
        $this->employees = new ArrayCollection();
    }

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : void
    {
        $this->name = $name;
    }

    public function getTeams() : Collection
    {
        return $this->teams;
    }

    public function getEmployees() : Collection
    {
        return $this->employees;
    }
}
