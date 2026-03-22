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

use ApiPlatform\Doctrine\Odm\Filter\FreeTextQueryFilter;
use ApiPlatform\Doctrine\Odm\Filter\OrFilter;
use ApiPlatform\Doctrine\Odm\Filter\PartialSearchFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
#[ApiResource(
    shortName: 'PostCard',
    operations: [
        new GetCollection(
            uriTemplate: '/post_cards_embedded',
            normalizationContext: ['hydra_prefix' => false],
            parameters: [
                'citySearch' => new QueryParameter(
                    filter: new PartialSearchFilter(),
                    property: 'address.city',
                ),
                'freeSearch' => new QueryParameter(
                    filter: new FreeTextQueryFilter(new OrFilter(new PartialSearchFilter())),
                    properties: ['address.city', 'address.street'],
                ),
            ],
        ),
    ]
)]
class PostCard
{
    #[ODM\Id(type: 'string', strategy: 'INCREMENT')]
    private ?string $id = null;

    #[ODM\Field(type: 'string')]
    private string $title;

    #[ODM\EmbedOne(targetDocument: PostCardAddress::class)]
    private PostCardAddress $address;

    public function __construct()
    {
        $this->address = new PostCardAddress();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getAddress(): PostCardAddress
    {
        return $this->address;
    }

    public function setAddress(PostCardAddress $address): self
    {
        $this->address = $address;

        return $this;
    }
}
