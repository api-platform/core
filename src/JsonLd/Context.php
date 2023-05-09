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

namespace ApiPlatform\JsonLd;

use Symfony\Component\Serializer\Annotation\SerializedName;

class Context
{
    /**
     * @param array<string ,mixed> $context
     */
    public function __construct(#[SerializedName('@context')] public array $context)
    {
    }
}
