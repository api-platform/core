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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaNestedOutputDto;

use ApiPlatform\Metadata\ApiResource;

#[ApiResource(description: 'A schema type', types: ['https://schema.org/Thing'], operations: [])]
class DocumentedRelated
{
    public string $name = '';
}
