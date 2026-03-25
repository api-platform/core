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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue7797;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use Doctrine\ORM\Mapping as ORM;

/**
 * @see https://github.com/api-platform/core/issues/7797
 */
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/issue7797_plans/{planId}/pairings',
            uriVariables: [
                'planId' => new Link(
                    fromClass: Plan::class,
                    security: 'true',
                ),
            ],
        ),
    ],
)]
#[ORM\Entity]
class Pairing
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public ?int $id = null;

    #[ORM\Column]
    public string $name = '';
}
