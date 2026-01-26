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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7689;

use ApiPlatform\Doctrine\Orm\State\Options as OrmOptions;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue7689\Issue7689Product;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[ApiResource(
    operations: [
        new Get(),
        new Post(),
    ],
    shortName: 'Issue7689Product',
    stateOptions: new OrmOptions(entityClass: Issue7689Product::class)
)]
#[Map(target: Issue7689Product::class)]
class Issue7689ProductDto
{
    public ?int $id = null;

    public string $name;

    public ?Issue7689CategoryDto $category = null;
}
