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

namespace ApiPlatform\Tests\Fixtures\TestBundle\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyDtoOutputFallbackToSameClass as DummyDtoOutputFallbackToSameClassDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyDtoOutputSameClass as DummyDtoOutputSameClassDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\OutputDtoDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyDtoOutputFallbackToSameClass;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyDtoOutputSameClass;

/**
 * @author Daniel West <daniel@silverback.is>
 */
final class OutputDtoSameClassTransformer implements DataTransformerInterface
{
    /**
     * @return object
     */
    public function transform($object, string $to, array $context = [])
    {
        if (
            !$object instanceof DummyDtoOutputFallbackToSameClass
            && !$object instanceof DummyDtoOutputFallbackToSameClassDocument
            && !$object instanceof DummyDtoOutputSameClass
            && !$object instanceof DummyDtoOutputSameClassDocument
        ) {
            throw new \InvalidArgumentException();
        }
        $object->ipsum = 'modified';

        return $object;
    }

    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return (($data instanceof DummyDtoOutputFallbackToSameClass || $data instanceof DummyDtoOutputFallbackToSameClassDocument) && OutputDtoDummy::class === $to)
            || (($data instanceof DummyDtoOutputSameClass || $data instanceof DummyDtoOutputSameClassDocument) && (DummyDtoOutputSameClass::class === $to || DummyDtoOutputSameClassDocument::class === $to));
    }
}
