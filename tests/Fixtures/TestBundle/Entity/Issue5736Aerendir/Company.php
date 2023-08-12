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
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
#[ORM\Table(name: 'issue5736_companies')]
#[API\ApiResource(
    normalizationContext: [
        AbstractNormalizer::GROUPS => ['company:read'],
    ],
    denormalizationContext: [
        AbstractNormalizer::GROUPS => ['company:write'],
    ],
    operations: [
        new API\GetCollection(
            uriTemplate: self::API_ENDPOINT,
        ),
        new API\Get(
            uriTemplate: self::API_RESOURCE,
        ),
        new API\Post(
            read: false,
            uriTemplate: self::API_ENDPOINT,
        ),
        new API\Put(
            uriTemplate: self::API_RESOURCE,
        ),
    ],
)]
class Company
{
    public const API_ID_PLACEHOLDER = 'issue5736_company';
    public const API_ENDPOINT = 'issue5736_companies';
    public const API_RESOURCE = '/' . self::API_ENDPOINT . '/{' . self::API_ID_PLACEHOLDER . '}';

    #[ORM\Column(type: 'integer', nullable: true)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column]
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
