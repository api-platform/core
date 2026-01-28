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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Patch(inputFormats: [
            'jsonld' => ['application/ld+json', 'application/merge-patch+json'],
            'toon' => ['text/ld+toon'], // for jsonld base
            'jsonld_toon' => ['text/ld+toon'], // explicit for jsonld base
            'jsonapi_toon' => ['text/vnd.api+toon']
        ]),
    ],
    formats: [
        'jsonld' => ['application/ld+json'],
        'toon' => ['text/ld+toon'], // for jsonld base
        'jsonld_toon' => ['text/ld+toon'], // explicit for jsonld base
        'jsonapi' => ['application/vnd.api+json'],
        'jsonapi_toon' => ['text/vnd.api+toon'],
        'hydra' => ['application/ld+json'],
        'hydra_toon' => ['text/ld+toon']
    ]
)]
#[ORM\Entity]
class ToonBook
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    public string $title;

    #[ORM\Column(type: 'string', length: 255)]
    public string $author;

    #[ORM\Column(type: 'integer')]
    public int $pages = 0;

    #[ORM\Column(type: 'boolean')]
    public bool $available = true;

    public function getId(): ?int
    {
        return $this->id;
    }
}
