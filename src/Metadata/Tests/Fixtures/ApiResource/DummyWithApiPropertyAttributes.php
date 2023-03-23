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

namespace ApiPlatform\Metadata\Tests\Fixtures\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(description: 'Hey PHP 8')]
#[ORM\Entity]
class DummyWithApiPropertyAttributes
{
    #[ApiProperty(identifier: true, description: 'the identifier')]
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    public $id;

    #[ORM\Column]
    public $filtered;

    #[ApiProperty]
    public $empty;

    #[ApiProperty(description: 'a foo')]
    public function getFoo(): int
    {
        return 0;
    }
}
