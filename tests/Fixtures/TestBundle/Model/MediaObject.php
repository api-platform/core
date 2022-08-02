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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Model;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Mutation;

/**
 * @author Mahmood Bazdar <mahmood@bazdar.me>
 */
#[ApiResource(graphQlOperations: [new Mutation(name: 'upload', resolver: 'app.graphql.mutation_resolver.upload_media_object', args: ['file' => ['type' => 'Upload!', 'description' => 'Upload a file']]), new Mutation(name: 'uploadMultiple', resolver: 'app.graphql.mutation_resolver.upload_multiple_media_object', args: ['files' => ['type' => '[Upload!]!', 'description' => 'Upload multiple files']])], types: ['https://schema.org/MediaObject'])]
class MediaObject
{
    #[ApiProperty(identifier: true)]
    public $id;

    public string $contentUrl;
}
