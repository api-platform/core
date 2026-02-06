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

namespace ApiPlatform\Doctrine\Common\Tests\Fixtures\TestBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entity with typed properties that are not initialized.
 * Simulates entities using PrePersist lifecycle callbacks.
 */
#[ORM\Entity]
class DummyWithUninitializedProperties
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    #[ORM\Column(length: 255)]
    public string $title;

    #[ORM\Column(length: 50)]
    public string $status;

    #[ORM\Column]
    public int $version;
}
