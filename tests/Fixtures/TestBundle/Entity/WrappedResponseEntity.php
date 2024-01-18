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
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\CustomOutputEntityWrapperDto;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(operations: [new Get(normalizationContext: ['groups' => ['read']], output: CustomOutputEntityWrapperDto::class
)])]
#[ORM\Entity]
class WrappedResponseEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    #[Groups(['read'])]
    public $id;
}
