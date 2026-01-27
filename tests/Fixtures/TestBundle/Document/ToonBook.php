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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Patch(inputFormats: ['jsonld' => ['application/ld+json', 'application/merge-patch+json'], 'toon' => ['text/ld+toon']]),
        new Delete(),
    ],
    formats: ['jsonld' => ['application/ld+json'], 'toon' => ['text/ld+toon']]
)]
#[ODM\Document]
class ToonBook
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;

    #[ODM\Field(type: 'string')]
    public string $title;

    #[ODM\Field(type: 'string')]
    public string $author;

    #[ODM\Field(type: 'int')]
    public int $pages = 0;

    #[ODM\Field(type: 'bool')]
    public bool $available = true;

    public function getId(): ?int
    {
        return $this->id;
    }
}
