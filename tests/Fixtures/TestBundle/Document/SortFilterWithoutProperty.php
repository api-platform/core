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

use ApiPlatform\Doctrine\Odm\Filter\SortFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
#[ApiResource(
    operations: [
        new GetCollection(
            parameters: [
                'sortNoProperty' => new QueryParameter(filter: new SortFilter()),
            ],
        ),
    ]
)]
class SortFilterWithoutProperty
{
    #[ODM\Id(type: 'int', strategy: 'INCREMENT')]
    public ?int $id = null;

    #[ODM\Field(type: 'string')]
    public string $name = '';
}
