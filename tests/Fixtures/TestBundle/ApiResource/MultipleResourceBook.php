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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;

#[ApiResource(
    uriTemplate: '/admin/multi_route_books',
)]
#[ApiResource(
    shortName: 'MultipleResourceBook2',
    uriTemplate: '/multi_route_books',
)]
class MultipleResourceBook
{
    #[ApiProperty(identifier: true)]
    public int $id;

    public string $title;

    public string $isbn;

    public function __construct(int $id = 0, string $title = '', string $isbn = '')
    {
        $this->id = $id;
        $this->title = $title;
        $this->isbn = $isbn;
    }
}
