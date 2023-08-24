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

#[ORM\Entity]
#[ORM\Table(name: 'issue5736_teams')]
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
            ],
        ),
        new API\Get(
            uriTemplate: self::API_RESOURCE,
            uriVariables: [
                Company::API_ID_PLACEHOLDER => new API\Link(fromClass: Company::class, toProperty: 'company', identifiers: ['id']),
                Team::API_ID_PLACEHOLDER    => new API\Link(fromClass: self::class, identifiers: ['id']),
            ],
        ),
        new API\Post(
            read: false,
            processor: SetCompany5736Processor::class,
            uriTemplate: self::API_ENDPOINT,
            uriVariables: [
                Company::API_ID_PLACEHOLDER => new API\Link(fromClass: Company::class, toProperty: 'company', identifiers: ['id']),
            ],
        ),
        new API\Put(
            uriTemplate: self::API_RESOURCE,
            uriVariables: [
                Company::API_ID_PLACEHOLDER => new API\Link(fromClass: Company::class, toProperty: 'company', identifiers: ['id']),
                Team::API_ID_PLACEHOLDER    => new API\Link(fromClass: self::class, identifiers: ['id']),
            ],
        ),
    ],
)]
class Team implements CompanyAwareInterface
{
    public const API_ID_PLACEHOLDER = 'issue5736_team';
    public const API_ENDPOINT = Company::API_RESOURCE . '/issue5736_teams';
    public const API_RESOURCE = self::API_ENDPOINT . '/{' . self::API_ID_PLACEHOLDER . '}';
    public const GROUP_NOR_READ    = 'team:read';
    public const GROUP_DENOR_WRITE = 'team:write';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    #[Groups([self::GROUP_NOR_READ])]
    private ?int $id;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'teams')]
    #[ORM\JoinColumn(name: 'company', nullable: false)]
    #[Groups([self::GROUP_NOR_READ])]
    private Company $company;

    /** @var Collection<Employee>  */
    #[ORM\OneToMany(targetEntity: Employee::class, mappedBy: 'team')]
    #[Groups([self::GROUP_NOR_READ, self::GROUP_DENOR_WRITE])]
    private Collection $employees;

    #[ORM\Column]
    #[Groups([self::GROUP_NOR_READ])]
    private string $name;

    public function __construct()
    {
        $this->employees = new ArrayCollection();
    }

    public function getCompany() : Company
    {
        return $this->company;
    }

    public function setCompany(Company $company) : void
    {
        $this->company = $company;
    }
}
