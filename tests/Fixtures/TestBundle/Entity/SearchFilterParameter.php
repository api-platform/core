<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\Mapping as ORM;

#[GetCollection(
    uriTemplate: 'search_filter_parameter',
    parameters: [
        'search' => new QueryParameter(filter: 'app_search_filter_via_parameter', property: 'foo'),
    ]
)]
#[ORM\Entity]
class SearchFilterParameter
{
    /**
     * @var int The id
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;
    #[ORM\Column(type: 'string')]
    private string $foo = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFoo(): string
    {
        return $this->foo;
    }

    public function setFoo(string $foo): void
    {
        $this->foo = $foo;
    }
}
