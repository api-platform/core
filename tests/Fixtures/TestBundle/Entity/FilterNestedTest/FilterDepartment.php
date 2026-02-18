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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\FilterNestedTest;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Department entity for testing nested filter support.
 */
#[ORM\Entity]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ]
)]
class FilterDepartment
{
    #[ORM\Id]
    #[ORM\Column(type: 'symfony_uuid')]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\ManyToOne(targetEntity: FilterCompany::class)]
    #[ORM\JoinColumn(nullable: false)]
    private FilterCompany $company;

    public function __construct()
    {
        $this->id = Uuid::v4();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCompany(): FilterCompany
    {
        return $this->company;
    }

    public function setCompany(FilterCompany $company): self
    {
        $this->company = $company;

        return $this;
    }
}
