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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7801;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue7801\Issue7801Category;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[ApiResource(
    operations: [
        new Get(),
    ],
    shortName: 'Issue7801Category',
    stateOptions: new Options(entityClass: Issue7801Category::class)
)]
#[Map(source: Issue7801Category::class)]
class Issue7801CategoryDto
{
    public ?int $id = null;

    public string $name;
}
