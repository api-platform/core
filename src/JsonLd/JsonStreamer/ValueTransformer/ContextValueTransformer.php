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

use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use Symfony\Component\JsonStreamer\ValueTransformer\ValueTransformerInterface;
use Symfony\Component\TypeInfo\Type;

final class ContextValueTransformer implements ValueTransformerInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function transform(mixed $value, array $options = []): mixed
    {
        if (!isset($options['operation'])) {
            throw new RuntimeException('Operation is not defined');
        }

        return $this->urlGenerator->generate('api_jsonld_context', ['shortName' => $options['operation']->getShortName()], $options['operation']->getUrlGenerationStrategy());
    }

    public static function getStreamValueType(): Type
    {
        return Type::string();
    }
}
