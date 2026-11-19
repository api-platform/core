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

use ApiPlatform\Doctrine\Orm\Filter\ExactFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(description: 'Hey PHP 8', parameters: ['filtered' => new QueryParameter(filter: new ExactFilter())])]
#[ORM\Entity]
class DummyPhp8
{
    #[ApiProperty(identifier: true, description: 'the identifier')]
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    public $id;
    #[ORM\Column]
    public $filtered;

    #[ApiProperty(description: 'a foo')]
    public function getFoo(): int
    {
        return 0;
    }
}
