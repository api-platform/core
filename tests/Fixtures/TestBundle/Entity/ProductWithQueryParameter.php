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

use ApiPlatform\Doctrine\Orm\Filter\ExactFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\PartialSearchFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ApiResource(
    operations: [
        new GetCollection(
            parameters: [
                'brand' => new QueryParameter(
                    filter: new ExactFilter(),
                ),
                'brandWithDescription' => new QueryParameter(
                    filter: new ExactFilter(),
                    description: 'Extra description about the filter',
                ),
                'search[:property]' => new QueryParameter(
                    filter: new PartialSearchFilter(),
                    properties: ['title', 'description']
                ),
                'filter[:property]' => new QueryParameter(
                    filter: new ExactFilter(),
                    properties: ['category', 'brand'],
                ),
                'order[:property]' => new QueryParameter(
                    filter: new OrderFilter(),
                    properties: ['rating']
                ),
                'exactBrand' => new QueryParameter(
                    filter: new ExactFilter(),
                    property: 'brand',
                    schema: ['type' => 'string']
                ),
                'exactCategory' => new QueryParameter(
                    filter: new ExactFilter(),
                    property: 'category',
                    castToArray: false
                ),
                'tags' => new QueryParameter(
                    filter: new ExactFilter(),
                    property: 'tags',
                    schema: ['anyOf' => [['type' => 'array', 'items' => ['type' => 'string']], ['type' => 'string']]]
                ),
            ]
        ),
    ]
)]
class ProductWithQueryParameter
{
    #[ORM\Id]
    #[ORM\Column()]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    public ?string $sku = null;

    #[ORM\Column(length: 255)]
    public ?string $title = null;

    #[ORM\Column(nullable: true)]
    public ?string $description = null;

    #[ORM\Column(nullable: true)]
    public ?string $category = null;

    #[ORM\Column(nullable: true)]
    public ?string $brand = null;

    #[ORM\Column(nullable: true)]
    public ?float $exactPrice = null;

    #[ORM\Column()]
    public int $rating = 0;

    #[ORM\Column()]
    public int $stock = 0;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['jsonb' => true])]
    public array $tags = [];

    public function getId(): ?int
    {
        return $this->id;
    }
}
