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

/**
 * Input DTO with required constructor args — no defaults, not nullable.
 *
 * Used to reproduce the Sylius failures where only the first missing
 * constructor argument is reported instead of all of them.
 */
final class JsonApiRequiredFieldsInputDto
{
    public function __construct(
        public string $title,
        public int $rating,
        public string $comment,
    ) {
    }
}
