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

namespace Workbench\App\ApiResource;

use ApiPlatform\Metadata\Get;

#[Get(output: NotAResource::class, provider: [Output::class, 'provide'])]
class Output
{
    public static function provide(): NotAResource
    {
        return new NotAResource();
    }
}
