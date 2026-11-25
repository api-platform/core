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

namespace ApiPlatform\JsonLd\JsonStreamer\ValueTransformer;

use Symfony\Component\JsonStreamer\Transformer\PropertyValueTransformerInterface as SymfonyPropertyValueTransformerInterface;
use Symfony\Component\JsonStreamer\ValueTransformer\ValueTransformerInterface as LegacySymfonyValueTransformerInterface;

// symfony/json-streamer 8.1 deprecates ValueTransformerInterface in favor of PropertyValueTransformerInterface.
if (interface_exists(SymfonyPropertyValueTransformerInterface::class)) {
    /**
     * @internal
     */
    interface PropertyValueTransformerInterface extends SymfonyPropertyValueTransformerInterface
    {
    }
} else {
    /**
     * @internal
     */
    interface PropertyValueTransformerInterface extends LegacySymfonyValueTransformerInterface
    {
    }
}
