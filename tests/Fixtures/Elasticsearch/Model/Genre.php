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
