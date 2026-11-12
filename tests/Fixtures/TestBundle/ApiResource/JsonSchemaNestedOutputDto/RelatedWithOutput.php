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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;

#[Get(output: RelatedOutput::class)]
class RelatedWithOutput
{
    #[ApiProperty(identifier: true)]
    public int $id = 0;
    public string $internal = '';
}
