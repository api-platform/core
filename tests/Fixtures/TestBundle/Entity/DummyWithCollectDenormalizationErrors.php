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
use ApiPlatform\Metadata\Post;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

#[ApiResource(
    operations: [
        new Post(uriTemplate: 'dummy_collect_denormalization'),
    ],
    collectDenormalizationErrors: true,
    extraProperties: ['rfc_7807_compliant_errors' => false]
)]
#[ORM\Entity]
class DummyWithCollectDenormalizationErrors
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    public ?bool $foo;

    #[ORM\Column(nullable: true)]
    public ?int $bar;

    #[ORM\Column(nullable: true)]
    private ?string $baz;

    #[ORM\Column(nullable: true)]
    private ?string $qux;

    #[ORM\Column(type: 'uuid', nullable: true)]
    public ?UuidInterface $uuid = null;

    #[ORM\ManyToOne(targetEntity: RelatedDummy::class)]
    public ?RelatedDummy $relatedDummy = null;

    #[ORM\ManyToMany(targetEntity: RelatedDummy::class)]
    public Collection|iterable $relatedDummies;

    public function __construct(string $baz, ?string $qux = null)
    {
        $this->baz = $baz;
        $this->qux = $qux;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFoo(): ?bool
    {
        return $this->foo;
    }

    public function setFoo(?bool $foo): void
    {
        $this->foo = $foo;
    }

    public function getBar(): ?int
    {
        return $this->bar;
    }

    public function setBar(?int $bar): void
    {
        $this->bar = $bar;
    }
}
