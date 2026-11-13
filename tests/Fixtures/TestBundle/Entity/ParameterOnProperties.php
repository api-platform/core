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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\PartialSearchFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ApiResource(
    uriTemplate: 'parameter_on_properties',
    operations: [
        new GetCollection(),
        new Get(),
    ]
)]
class ParameterOnProperties
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string')]
    #[QueryParameter(key: 'qname', filter: new PartialSearchFilter())]
    private string $name = '';

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $description = null;

    public function __construct(string $name = '', ?string $description = null)
    {
        $this->name = $name;
        $this->description = $description;
    }

    public function getId(): ?int
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }
}
