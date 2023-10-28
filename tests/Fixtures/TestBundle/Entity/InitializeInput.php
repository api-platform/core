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
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\InitializeInputDto;
use ApiPlatform\Tests\Fixtures\TestBundle\State\InitializeInputProcessor;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(input: InitializeInputDto::class, processor: InitializeInputProcessor::class)]
#[ORM\Entity]
class InitializeInput
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    public $id;
    #[ORM\Column]
    public $manager;
    #[ORM\Column]
    public $name;
}
