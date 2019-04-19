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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyDtoNoInput as DummyDtoNoInputDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\OutputDto;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyDtoNoInput;

final class DummyDtoNoInputToOutputDtoDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($object, string $to, array $context = [])
    {
        if (!$object instanceof DummyDtoNoInput && !$object instanceof DummyDtoNoInputDocument) {
            throw new \InvalidArgumentException();
        }

        $output = new OutputDto();
        $output->id = $object->getId();
        $output->bat = (string) $object->lorem;
        $output->baz = (float) $object->ipsum;

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return ($data instanceof DummyDtoNoInput || $data instanceof DummyDtoNoInputDocument) && OutputDto::class === $to;
    }
}
