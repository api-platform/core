<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\Get;

#[Get(uriTemplate: '/post_with_iri_collection_item/{id}')]
class PostWithCollectionIriItem
{
    public function __construct(public string $id) {}
}
