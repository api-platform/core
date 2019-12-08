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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Model;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;

/**
 * @ApiResource(
 *     iri="http://schema.org/MediaObject",
 *     graphql={
 *         "upload"={
 *             "mutation"="app.graphql.mutation_resolver.upload_media_object",
 *             "args"={
 *                 "file"={"type"="Upload!", "description"="Upload a file"}
 *             }
 *         },
 *         "uploadMultiple"={
 *             "mutation"="app.graphql.mutation_resolver.upload_multiple_media_object",
 *             "args"={
 *                 "files"={"type"="[Upload!]!", "description"="Upload multiple files"}
 *             }
 *         }
 *     }
 * )
 *
 * @author Mahmood Bazdar <mahmood@bazdar.me>
 */
class MediaObject
{
    /**
     * @ApiProperty(identifier=true)
     */
    public $id;

    /**
     * @var string
     */
    public $contentUrl;
}
