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
use ApiPlatform\Serializer\AbstractItemNormalizer;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyDtoCustom as DummyDtoCustomDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\CustomInputDto;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyDtoCustom;

final class CustomInputDtoDataTransformer implements DataTransformerInterface
{
    /**
     * @return object
     */
    public function transform($object, string $to, array $context = [])
    {
        if (!$object instanceof CustomInputDto) {
            throw new \InvalidArgumentException();
        }

        /**
         * @var \ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyDtoCustom
         */
        $resourceObject = $context[AbstractItemNormalizer::OBJECT_TO_POPULATE] ?? new $context['resource_class']();
        $resourceObject->lorem = $object->foo;
        $resourceObject->ipsum = (string) $object->bar;

        return $resourceObject;
    }

    public function supportsTransformation($object, string $to, array $context = []): bool
    {
        return (DummyDtoCustom::class === $to || DummyDtoCustomDocument::class === $to) && CustomInputDto::class === ($context['input']['class'] ?? null);
    }
}
