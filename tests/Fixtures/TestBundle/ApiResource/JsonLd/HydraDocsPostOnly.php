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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;

/**
 * Only a POST: a NotExposed GET is added to generate the IRIs.
 */
#[ApiResource(
    shortName: 'JsonLdHydraDocsPostOnly',
    operations: [
        new Post(uriTemplate: '/jsonld_hydra_docs_post_only'),
    ],
)]
class HydraDocsPostOnly
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public string $name = '';
}
