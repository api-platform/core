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

use ApiPlatform\Doctrine\Orm\Filter\PartialSearchFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    normalizationContext: ['hydra_prefix' => false],
    parameters: [
        'search[:property]' => new QueryParameter(
            filter: new PartialSearchFilter(),
            properties: ['description', 'name']
        ),
    ]
)]
#[ORM\Entity]
class SuperHero
{
    #[ORM\Id, ORM\Column, ORM\GeneratedValue]
    public ?int $id = null;

    #[ORM\Column]
    public string $name = '';

    #[ORM\Column]
    public string $description = '';

    #[ORM\Column]
    public string $secret = '';
}
