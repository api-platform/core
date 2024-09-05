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

namespace ApiPlatform\Symfony\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Tests\Fixtures\NotAResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Resource linked to a standard object.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
#[ApiResource(filters: ['my_dummy.property'], normalizationContext: ['groups' => ['contain_non_resource']])]
#[ORM\Entity]
class ContainNonResource
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[Groups('contain_non_resource')]
    public $id;
    /**
     * @var ContainNonResource
     */
    #[Groups('contain_non_resource')]
    public $nested;
    /**
     * @var NotAResource
     */
    #[Groups('contain_non_resource')]
    public $notAResource;
}
