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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Symfony\Component\WebLink\Link as WebLink;

/**
 * Templated links belong to the Link-Template header (RFC 9652), the others to the Link header (RFC 8288).
 */
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: 'link_templates/{id}',
            links: [
                new WebLink('describedby', '/docs.jsonld'),
                new WebLink('author', '/link_templates/{id}/author'),
            ],
            provider: [self::class, 'provide'],
        ),
    ],
    graphQlOperations: []
)]
class LinkTemplateResource
{
    public int $id;

    public static function provide(): self
    {
        $s = new self();
        $s->id = 1;

        return $s;
    }
}
