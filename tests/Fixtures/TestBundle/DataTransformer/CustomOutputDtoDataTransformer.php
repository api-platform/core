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
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyDtoCustom as DummyDtoCustomDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\CustomOutputDto;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyDtoCustom;

final class CustomOutputDtoDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($object, string $to, array $context = [])
    {
        if ($object instanceof \Traversable) {
            foreach ($object as &$value) {
                $value = $this->doTransformation($value);
            }

            return $object;
        }

        return $this->doTransformation($object);
    }

    private function doTransformation($object): CustomOutputDto
    {
        $output = new CustomOutputDto();
        $output->foo = $object->lorem;
        $output->bar = (int) $object->ipsum;

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($object, string $to, array $context = []): bool
    {
        if ($object instanceof \IteratorAggregate) {
            $iterator = $object->getIterator();
            if ($iterator instanceof \Iterator) {
                $object = $iterator->current();
            }
        }

        return ($object instanceof DummyDtoCustom || $object instanceof DummyDtoCustomDocument) && CustomOutputDto::class === $to;
    }
}
