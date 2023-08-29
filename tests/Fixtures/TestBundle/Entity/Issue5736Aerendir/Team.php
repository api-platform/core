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
use ApiPlatform\Tests\Fixtures\TestBundle\State\SetCompany5736Processor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

#[ORM\Entity]
#[ORM\Table(name: 'issue5736_teams')]
#[API\ApiResource(
    normalizationContext: [
        AbstractNormalizer::GROUPS => [Team::GROUP_NOR_READ],
        AbstractObjectNormalizer::ENABLE_MAX_DEPTH => true,
    ],
    denormalizationContext: [
        AbstractNormalizer::GROUPS => [Team::GROUP_DENOR_WRITE],
        AbstractObjectNormalizer::ENABLE_MAX_DEPTH => true,
    ],
    operations: [
        new API\GetCollection(
            uriTemplate: Team::API_ENDPOINT,
            uriVariables: [
                Company::API_ID_PLACEHOLDER => new API\Link(fromClass: Company::class, toProperty: 'company', identifiers: ['id']),
            ],
        ),
        new API\Get(
            uriTemplate: Team::API_RESOURCE,
            uriVariables: [
                Company::API_ID_PLACEHOLDER => new API\Link(fromClass: Company::class, toProperty: 'company', identifiers: ['id']),
                Team::API_ID_PLACEHOLDER    => new API\Link(fromClass: Team::class, identifiers: ['id']),
            ],
        ),
        new API\Post(
            read: false,
            processor: SetCompany5736Processor::class,
            uriTemplate: Team::API_ENDPOINT,
            uriVariables: [
                Company::API_ID_PLACEHOLDER => new API\Link(fromClass: Company::class, toProperty: 'company', identifiers: ['id']),
            ],
        ),
        new API\Put(
            uriTemplate: Team::API_RESOURCE,
            uriVariables: [
                Company::API_ID_PLACEHOLDER => new API\Link(fromClass: Company::class, toProperty: 'company', identifiers: ['id']),
                Team::API_ID_PLACEHOLDER    => new API\Link(fromClass: Team::class, identifiers: ['id']),
            ],
        ),
    ],
)]
class Team implements CompanyAwareInterface
{
    public const API_ID_PLACEHOLDER = 'issue5736_team';
    public const API_ENDPOINT = Company::API_RESOURCE . '/issue5736_teams';
    public const API_RESOURCE = Team::API_ENDPOINT . '/{' . Team::API_ID_PLACEHOLDER . '}';
    public const GROUP_NOR_READ    = 'team:read';
    public const GROUP_DENOR_WRITE = 'team:write';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    #[Groups([Team::GROUP_NOR_READ, Team::GROUP_DENOR_WRITE])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'teams')]
    #[ORM\JoinColumn(name: 'company', nullable: false)]
    #[Groups([Team::GROUP_NOR_READ, Team::GROUP_DENOR_WRITE])]
    private ?Company $company = null;

    #[ORM\Column]
    #[Groups([Team::GROUP_NOR_READ, Team::GROUP_DENOR_WRITE])]
    private string $name;

    /** @var Collection<Employee>  */
    #[ORM\OneToMany(targetEntity: Employee::class, mappedBy: 'team', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups([Team::GROUP_NOR_READ, Team::GROUP_DENOR_WRITE])]
    private Collection $employees;

    public function __construct()
    {
        $this->employees = new ArrayCollection();
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getCompany() : ?Company
    {
        return $this->hasCompany() ? $this->company : null;
    }

    public function hasCompany() : bool
    {
        return isset($this->company);
    }

    public function setCompany(Company $company) : void
    {
        $this->company = $company;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : void
    {
        $this->name = $name;
    }

    public function getEmployees() : Collection
    {
        return $this->employees;
    }

    public function setEmployees(Collection $employees) : void
    {
        $this->resetEmployees();

        foreach ($employees as $employee) {
            $this->addEmployee($employee);
        }
    }

    public function addEmployee(Employee $employee) : void
    {
        $predictate = static fn (int $key, Employee $element): bool => $employee->hasTeam() && $element->hasTeam() && $element->getTeam() === $employee->getTeam();

        if (false === $this->employees->exists($predictate)) {
            $this->employees->add($employee);

            // This has to be after the adding of the line to an infinite loop
            $employee->setTeam($this);
        }
    }

    public function removeEmployee(Employee $employee) : void
    {
        $this->employees->removeElement($employee);
    }

    private function resetEmployees(): void
    {
        $this->employees = new ArrayCollection();
    }
}
