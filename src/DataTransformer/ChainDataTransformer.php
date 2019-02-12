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

namespace ApiPlatform\Core\DataTransformer;

/**
 * Transforms an Input to a Resource object.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class ChainDataTransformer implements DataTransformerInterface
{
    private $transformers;

    public function __construct(iterable $transformers)
    {
        $this->transformers = $transformers;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($object, string $to, array $context = [])
    {
        foreach ($this->transformers as $transformer) {
            if ($transformer->supportsTransformation($object, $to, $context)) {
                return $transformer->transform($object, $to, $context);
            }
        }

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($object, string $to, array $context = []): bool
    {
        foreach ($this->transformers as $transformer) {
            if ($transformer->supportsTransformation($object, $to, $context)) {
                return true;
            }
        }

        return false;
    }
}
