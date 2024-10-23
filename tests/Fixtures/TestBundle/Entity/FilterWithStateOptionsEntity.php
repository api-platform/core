<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class FilterWithStateOptionsEntity
{
    public function __construct(
        #[ORM\Column(type: 'integer')]
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'AUTO')]
        public ?int $id = null,
        #[ORM\Column(type: 'date_immutable', nullable: true)]
        public ?\DateTimeImmutable $dummyDate = null,
        #[ORM\Column(type: 'string', nullable: true)]
        public ?string $name = null
    ) {}
}
