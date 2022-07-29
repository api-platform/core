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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
class ReadableOnlyProperty
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ApiProperty(writable: false)]
    #[ORM\Column]
    private string $name = 'Read only';

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName($name): never
    {
        throw new \Exception('Can not write name.');
    }

    public function getName(): string
    {
        return $this->name;
    }
}
