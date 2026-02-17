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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Doctrine\Odm\State\Options;
use ApiPlatform\JsonLd\ContextBuilder;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\PartialPaginationMappedDocument;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[ApiResource(
    operations: [
        new GetCollection(
            paginationItemsPerPage: 3,
            normalizationContext: [ContextBuilder::HYDRA_CONTEXT_HAS_PREFIX => false],
        ),
    ],
    stateOptions: new Options(documentClass: PartialPaginationMappedDocument::class),
)]
#[Map(target: PartialPaginationMappedDocument::class)]
final class PaginationMappedResource
{
    #[Map(if: false)]
    public ?int $id = null;

    #[Map(target: 'name')]
    public string $title;
}
