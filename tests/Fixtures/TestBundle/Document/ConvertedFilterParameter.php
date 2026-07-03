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

use ApiPlatform\Doctrine\Odm\Filter\ExactFilter;
use ApiPlatform\Doctrine\Odm\Filter\SortFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Tests that modern parameter filters (ExactFilter, SortFilter) work with
 * QueryParameter on a multi-word property when a nameConverter is configured
 * (issue #8380).
 */
#[ApiResource(
    operations: [
        new GetCollection(
            parameters: [
                'nameConverted' => new QueryParameter(
                    filter: new ExactFilter(),
                    property: 'nameConverted',
                ),
                'orderNameConverted' => new QueryParameter(
                    filter: new SortFilter(),
                    property: 'nameConverted',
                ),
            ],
        ),
    ],
)]
#[ODM\Document]
class ConvertedFilterParameter
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;

    #[ODM\Field(type: 'string')]
    public string $nameConverted = '';

    public function getId(): ?int
    {
        return $this->id;
    }
}
