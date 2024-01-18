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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;

/**
 * @author Urban Suppiger <urban@suppiger.net>
 */
#[ApiResource(
    operations: [
        new Get(),
        new Post(),
    ]
)]
#[ORM\Entity]
class ExtraPropertiesOnProperty
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public $id;

    #[ORM\ManyToOne(targetEntity: RelatedDummy::class, cascade: ['persist'])]
    #[ApiProperty(extraProperties: ['cacheDependencies' => ['overrideRelationTag']])]
    public ?RelatedDummy $relatedDummy = null;
}
