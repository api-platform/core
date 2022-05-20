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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyCustomQuery as DummyCustomQueryDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\OutputDto;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCustomQuery;

final class DummyCustomQueryDtoDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     *
     * @return object
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

    private function doTransformation($object): OutputDto
    {
        $output = new OutputDto();
        $output->baz = 46;
        $output->bat = $object->message;

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

        return ($object instanceof DummyCustomQuery || $object instanceof DummyCustomQueryDocument) && OutputDto::class === $to;
    }
}
