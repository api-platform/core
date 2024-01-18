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

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;

#[Post(uriTemplate: '/uri_variable_mask/{id}/{direction}', processor: [UriVariableMask::class, 'process'])]
#[ORM\Entity]
class UriVariableMask
{
    #[ORM\Column(type: 'string')]
    #[ORM\Id]
    public ?string $id;

    public static function process($data, Operation $operation, array $uriVariables = [])
    {
        return $data;
    }
}
