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

use ApiPlatform\Laravel\Eloquent\State\CollectionProvider;
use ApiPlatform\Laravel\Eloquent\State\ItemProvider;
use ApiPlatform\Laravel\Eloquent\State\Options;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Workbench\App\Models\Book;

#[ApiResource(
    operations: [
        new GetCollection(provider: CollectionProvider::class),
        new Get(provider: ItemProvider::class),
    ],
    stateOptions: new Options(modelClass: Book::class),
)]
class ResourceWithModel
{
    #[ApiProperty(identifier: true)]
    public ?string $id = null;
}
