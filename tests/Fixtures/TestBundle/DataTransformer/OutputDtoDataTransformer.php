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
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyDtoInputOutput as DummyDtoInputOutputDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\OutputDto;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyDtoInputOutput;

final class OutputDtoDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($object, string $to, array $context = [])
    {
        if (!$object instanceof DummyDtoInputOutput && !$object instanceof DummyDtoInputOutputDocument) {
            throw new \InvalidArgumentException();
        }

        $output = new OutputDto();
        $output->id = $object->id;
        $output->bat = (string) $object->str;
        $output->baz = (float) $object->num;

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return ($data instanceof DummyDtoInputOutput || $data instanceof DummyDtoInputOutputDocument) && OutputDto::class === $to;
    }
}
