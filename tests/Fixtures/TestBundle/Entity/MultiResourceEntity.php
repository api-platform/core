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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ApiResource(
    shortName: 'AdminMultiResource',
    operations: [
        new Get(uriTemplate: '/admin/multi_resources/{id}'),
        new GetCollection(uriTemplate: '/admin/multi_resources'),
    ],
)]
#[ApiResource(
    shortName: 'MultiResource',
    operations: [
        new Get(uriTemplate: '/multi_resources/{id}'),
        new GetCollection(uriTemplate: '/multi_resources'),
    ],
)]
class MultiResourceEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 255)]
    public string $title = '';
}
