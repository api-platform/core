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

namespace ApiPlatform\JsonSchema\Tests\Fixtures\Enum;

use ApiPlatform\Metadata\ApiProperty;

/**
 * An enumeration of genders.
 */
enum GenderTypeEnum: string
{
    /* The male gender. */
    case MALE = 'male';

    #[ApiProperty(description: 'The female gender.')]
    case FEMALE = 'female';
}
