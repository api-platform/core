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
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;

/**
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
#[ApiResource(
    operations: [new Get(), new Put(read: false, allowCreate: false)],
    extraProperties: [
        'custom_put' => true,
    ]
)]
#[Entity]
class CustomPut
{
    #[Id]
    #[Column]
    public ?int $id = null;

    #[Column]
    public string $foo = '';

    #[Column]
    public string $bar = '';
}
