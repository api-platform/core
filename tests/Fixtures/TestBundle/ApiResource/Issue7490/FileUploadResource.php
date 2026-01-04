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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7490;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Symfony\Component\HttpFoundation\File\File;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/issue7490_file_uploads',
        ),
    ]
)]
class FileUploadResource
{
    public ?File $file = null;
}
