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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;

#[ApiResource(description: "Hey PHP 8")]
class DummyPhp8
{
    #[ApiProperty(identifier: true, description: 'the identifier')]
    public $id;

    #[ApiProperty(description: 'a foo')]
    public function getFoo(): int
    {
        return 0;
    }
}
