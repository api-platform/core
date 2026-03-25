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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue7801\Issue7801Product;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ],
    shortName: 'Issue7801Product',
    stateOptions: new Options(entityClass: Issue7801Product::class)
)]
#[Map(source: Issue7801Product::class)]
class Issue7801ProductDto
{
    public ?int $id = null;

    public string $name;

    public ?Issue7801CategoryDto $category = null;
}
