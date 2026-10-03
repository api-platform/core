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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource;

/**
 * Extends a mapped subtype without being declared in the discriminator map: its own properties must never be exposed.
 */
final class DiscriminatedSignedFictionBook extends DiscriminatedFictionBook
{
    public ?string $signature = null;
}
