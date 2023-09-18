<?php

declare(strict_types=1);

namespace ApiPlatform\Tests\Fixtures\Elasticsearch\Model;

use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource]
class Genre
{
    #[Groups(['genre:read', 'book:read'])]
    public ?string $id = null;

    #[Groups(['genre:read', 'book:read'])]
    public ?string $name = null;
}
