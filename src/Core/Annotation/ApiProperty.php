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

namespace ApiPlatform\Core\Annotation;

use ApiPlatform\Metadata\ApiProperty;

class_alias(ApiProperty::class, 'ApiPlatform\Core\Annotation\ApiProperty');
trigger_error('The namespace ApiPlatform\Core is deprecated, use ApiPlatform instead', E_USER_DEPRECATED);
