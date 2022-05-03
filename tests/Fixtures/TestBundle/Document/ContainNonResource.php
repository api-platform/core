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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Tests\Fixtures\NotAResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Resource linked to a standard object.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
#[ApiResource(filters: ['my_dummy.property'], normalizationContext: ['groups' => ['contain_non_resource']])]
#[ODM\Document]
class ContainNonResource
{
    #[Groups('contain_non_resource')]
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
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
