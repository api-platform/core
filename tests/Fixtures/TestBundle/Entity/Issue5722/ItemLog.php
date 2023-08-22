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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5722;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ApiResource(
    shortName: 'ItemLogIssue5722',
    operations: [
        new GetCollection(),
    ],
)]
#[ApiResource(
    uriTemplate: '/events/{uuid}/logs{._format}',
    operations: [
        new GetCollection(),
    ],
    uriVariables: [
        'uuid' => new Link(fromProperty: 'logs', fromClass: Event::class),
    ],
)]
#[ORM\Entity]
class ItemLog
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ApiProperty(readable: false, writable: false, identifier: false)] // identifier was false before 3.1.14, changing this to true fixed some errors
    private ?int $id = null;

    #[ApiProperty(writable: false, identifier: true)]
    #[ORM\Column(type: 'uuid', unique: true)]
    public $uuid;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'logs')]
    public ?Event $item = null;

    #[ApiProperty(required: true)]
    #[ORM\Column]
    public string $action = 'insert';

    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->uuid = Uuid::uuid4();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
