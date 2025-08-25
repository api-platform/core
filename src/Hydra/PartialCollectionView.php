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

namespace ApiPlatform\Hydra;

use Symfony\Component\JsonStreamer\Attribute\StreamedName;

class PartialCollectionView
{
    #[StreamedName('@type')]
    public string $type = 'PartialCollectionView';

    public function __construct(
        #[StreamedName('@id')]
        public string $id,
        #[StreamedName('first')]
        public ?string $first = null,
        #[StreamedName('last')]
        public ?string $last = null,
        #[StreamedName('previous')]
        public ?string $previous = null,
        #[StreamedName('next')]
        public ?string $next = null,
    ) {
    }
}
