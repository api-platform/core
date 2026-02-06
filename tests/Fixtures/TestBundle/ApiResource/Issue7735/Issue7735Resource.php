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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7735;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue7735\Issue7735Entity;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[ApiResource(
    stateOptions: new Options(entityClass: Issue7735Entity::class),
    operations: [
        new Post(
            uriTemplate: '/issue7735_resources',
        ),
    ]
)]
#[Map(target: Issue7735Entity::class)]
class Issue7735Resource
{
    public ?string $id = null;
    public string $name;
    public ?string $generatedValue = null;
}
