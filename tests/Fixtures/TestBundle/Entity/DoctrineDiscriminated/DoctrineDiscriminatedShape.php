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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\DoctrineDiscriminated;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class table inheritance with integer discriminator values.
 */
#[ApiResource(operations: [new Get(), new GetCollection(), new Post(), new Patch()])]
#[ORM\Entity]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'shapeType', type: 'integer')]
#[ORM\DiscriminatorMap([1 => DoctrineDiscriminatedCircle::class, 2 => DoctrineDiscriminatedSquare::class])]
abstract class DoctrineDiscriminatedShape
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public ?int $id = null;

    #[ORM\Column]
    public string $color = '';
}
