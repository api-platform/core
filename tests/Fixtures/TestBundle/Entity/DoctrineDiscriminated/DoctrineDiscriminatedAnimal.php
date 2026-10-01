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
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\DiscriminatorMap;

/**
 * The serializer discriminator map is the API contract: it takes precedence over the Doctrine one.
 */
#[ApiResource(operations: [new Get(), new GetCollection(), new Post()])]
#[ORM\Entity]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['dog' => DoctrineDiscriminatedDog::class, 'cat' => DoctrineDiscriminatedCat::class])]
#[DiscriminatorMap(typeProperty: 'species', mapping: ['canine' => DoctrineDiscriminatedDog::class])]
abstract class DoctrineDiscriminatedAnimal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public ?int $id = null;

    #[ORM\Column]
    public string $name = '';
}
