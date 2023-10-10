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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\CreateProvider;
use Symfony\Component\Validator\Constraints as Assert;

#[Post(
    uriTemplate: '/subresource_categories/{id}/subresource_bikes',
    uriVariables: [
        'id' => new Link(
            fromClass: SubresourceCategory::class,
            toProperty: 'category',
            identifiers: ['id']
        ),
    ],
    provider: CreateProvider::class,
    processor: [SubresourceBike::class, 'process']
)]
#[Post(
    uriTemplate: '/subresource_categories_with_create_provider/{id}/subresource_bikes',
    uriVariables: [
        'id' => new Link(
            fromClass: SubresourceCategory::class,
            toProperty: 'category',
            identifiers: ['id']
        ),
    ],
    provider: CreateProvider::class,
    processor: [SubresourceBike::class, 'process'],
    extraProperties: ['parent_uri_template' => '/subresource_categories_with_create_provider/{id}']
)]
/**
 * @see SubresourceCategory
 */
class SubresourceBike
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    #[Assert\NotBlank]
    public ?string $name = null;

    #[Assert\NotNull]
    public ?SubresourceCategory $category = null;

    public static function process(mixed $data): self
    {
        $data->id = 1;

        return $data;
    }
}
