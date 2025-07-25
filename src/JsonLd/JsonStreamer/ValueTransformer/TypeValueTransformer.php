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

use ApiPlatform\Hydra\Collection;
use ApiPlatform\Metadata\Exception\RuntimeException;
use Symfony\Component\JsonStreamer\ValueTransformer\ValueTransformerInterface;
use Symfony\Component\TypeInfo\Type;

final class TypeValueTransformer implements ValueTransformerInterface
{
    public function transform(mixed $value, array $options = []): mixed
    {
        if ($options['_current_object'] instanceof Collection) {
            return 'Collection';
        }

        if (!isset($options['operation'])) {
            throw new RuntimeException('Operation is not defined');
        }

        return $options['operation']->getShortName();
    }

    public static function getStreamValueType(): Type
    {
        return Type::string();
    }
}
