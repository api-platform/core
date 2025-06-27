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
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use Symfony\Component\JsonStreamer\ValueTransformer\ValueTransformerInterface;
use Symfony\Component\TypeInfo\Type;

final class IriValueTransformer implements ValueTransformerInterface
{
    public function __construct(
        private readonly IriConverterInterface $iriConverter,
    ) {
    }

    public function transform(mixed $value, array $options = []): mixed
    {
        if (!isset($options['operation'])) {
            throw new RuntimeException('Operation is not defined');
        }

        if ($options['_current_object'] instanceof Collection) {
            return $this->iriConverter->getIriFromResource($options['operation']->getClass(), UrlGeneratorInterface::ABS_PATH, $options['operation']);
        }

        return $this->iriConverter->getIriFromResource(
            $options['_current_object'],
            UrlGeneratorInterface::ABS_PATH,
            $options['operation'] instanceof CollectionOperationInterface ? null : $options['operation'],
        );
    }

    public static function getStreamValueType(): Type
    {
        return Type::string();
    }
}
