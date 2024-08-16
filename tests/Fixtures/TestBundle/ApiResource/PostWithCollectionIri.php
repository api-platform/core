<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\NotExposed;
use ApiPlatform\Metadata\Post;

#[NotExposed(uriTemplate: '/post_with_iri_collection/{id}/{slug}')]
#[Post(read: false, deserialize: false, uriTemplate: '/post_with_iri_collection/{id}/{slug}', processor: [self::class, 'process'])]
class PostWithCollectionIri
{
    public string $id;
    public string $slug;

    public static function process(): array {
        return [
            new PostWithCollectionIriItem('1'),
            new PostWithCollectionIriItem('2'),
        ];
    }
}
