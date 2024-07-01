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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5501;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['location:read_collection']]
        ),
    ]
)]
class BrokenDocs
{
    public ?int $id = null;

    /**
     * @var ?ArrayCollection<Related>
     */
    #[Groups(['location:write', 'location:read_collection'])]
    public ?ArrayCollection $locations;
}
