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

namespace ApiPlatform\Serializer;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event class for normalizer events (normalize attributes).
 *
 * @author Urban Suppiger <urban@suppiger.net>
 */
class NormalizeAttributeEvent extends Event
{
    public const NORMALIZE_ATTRIBUTE = 'api_platform.normalizer.normalize_attribute';

    public function __construct(
        public mixed $object,
        public ?string $format = null,
        public array $context = [],
        public ?string $iri = null,
        public array|string|int|float|bool|\ArrayObject|null $data = null,
        public ?string $attribute = null,
        public ?ApiProperty $propertyMetadata = null,
        public ?Type $type = null,
        public array $childContext = [],
    ) {
    }
}
