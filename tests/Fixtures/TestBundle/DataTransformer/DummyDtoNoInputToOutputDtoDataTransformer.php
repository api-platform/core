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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyDtoNoInput as DummyDtoNoInputDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\Document\OutputDto as OutputDtoDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\OutputDto;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyDtoNoInput;

final class DummyDtoNoInputToOutputDtoDataTransformer implements DataTransformerInterface
{
    /**
     * @return object
     */
    public function transform($object, string $to, array $context = [])
    {
        if (!$object instanceof DummyDtoNoInput && !$object instanceof DummyDtoNoInputDocument) {
            throw new \InvalidArgumentException();
        }

        $output = $object instanceof DummyDtoNoInput ? new OutputDto() : new OutputDtoDocument();
        $output->id = $object->getId();
        $output->bat = (string) $object->lorem;
        $output->baz = (float) $object->ipsum;

        return $output;
    }

    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return ($data instanceof DummyDtoNoInput || $data instanceof DummyDtoNoInputDocument) && \in_array($to, [OutputDto::class, OutputDtoDocument::class], true);
    }
}
