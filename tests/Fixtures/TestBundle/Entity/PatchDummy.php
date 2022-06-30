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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
#[ApiResource(operations: [new Get(), new Patch(inputFormats: ['json' => ['application/merge-patch+json'], 'jsonapi'], normalizationContext: ['skip_null_values' => true]), new Post(), new GetCollection()])]
#[ORM\Entity]
class PatchDummy
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public $id;
    #[ORM\Column(nullable: true)]
    public $name;
}
