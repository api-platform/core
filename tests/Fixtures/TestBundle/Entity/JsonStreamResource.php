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
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'json_stream_resource')]
#[ApiResource(
    jsonStream: true,
    paginationEnabled: false,
    normalizationContext: ['hydra_prefix' => false]
)]
class JsonStreamResource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    #[ORM\Column(length: 255)]
    public string $title;

    #[ORM\Column(type: 'datetime_immutable')]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'date_immutable')]
    public \DateTimeImmutable $publishedAt;

    #[ORM\Column(type: 'integer')]
    public int $views;

    #[ORM\Column(type: 'float')]
    public float $rating;

    #[ORM\Column(type: 'boolean')]
    public bool $isFeatured;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    public string $price;
}
