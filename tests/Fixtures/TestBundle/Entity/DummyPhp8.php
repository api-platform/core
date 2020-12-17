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

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
#[ApiResource(description: "Hey PHP 8")]
class DummyPhp8
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    #[ApiProperty(identifier: true, description: 'the identifier')]
    public $id;

    /**
     * @ORM\Column
     */
    #[ApiFilter(SearchFilter::class)]
    public $filtered;

    #[ApiProperty(description: 'a foo')]
    public function getFoo(): int
    {
        return 0;
    }
}
