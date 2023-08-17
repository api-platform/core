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

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event class for normalizer events (normalize items).
 *
 * @author Urban Suppiger <urban@suppiger.net>
 */
class NormalizeItemEvent extends Event
{
    public const NORMALIZE_ITEM_PRE = 'api_platform.normalizer.normalize_item.pre';
    public const NORMALIZE_ITEM_POST = 'api_platform.normalizer.normalize_item.post';
    public const NORMALIZE_RELATION = 'api_platform.normalizer.normalize_relation';
    public const JSONAPI_NORMALIZE_RELATION = 'api_platform.jsonapi.normalizer.normalize_relation';

    public function __construct(
        public mixed $object,
        public ?string $format = null,
        public array $context = [],
        public ?string $iri = null,
        public array|string|int|float|bool|\ArrayObject|null $data = null
    ) {
    }
}
