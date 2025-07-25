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
use Symfony\Component\Serializer\Annotation\SerializedName;

class IriTemplateMapping
{
    #[StreamedName('@type')]
    #[SerializedName('@type')]
    public string $type = 'IriTemplateMapping';

    public function __construct(
        public string $variable,
        public ?string $property,
        public bool $required = false,
    ) {
    }
}
