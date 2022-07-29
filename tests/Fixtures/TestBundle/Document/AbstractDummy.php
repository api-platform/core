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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Abstract Dummy.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
#[ApiResource(operations: [new Get(), new Put(), new Delete(), new GetCollection(), new Post()], filters: ['my_dummy.mongodb.search', 'my_dummy.mongodb.order', 'my_dummy.mongodb.date'])]
#[ODM\Document]
#[ODM\InheritanceType('SINGLE_COLLECTION')]
#[ODM\DiscriminatorField(value: 'discr')]
#[ODM\DiscriminatorMap(['concrete' => ConcreteDummy::class])]
abstract class AbstractDummy
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;
    #[ApiProperty(types: ['https://schema.org/name'])]
    #[Assert\NotBlank]
    #[ODM\Field]
    private ?string $name = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
