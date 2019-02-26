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
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyDtoInputOutput as DummyDtoInputOutputDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\InputDto;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyDtoInputOutput;

final class InputDtoDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($object, string $to, array $context = [])
    {
        /**
         * @var \ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\InputDto
         */
        $data = $object;

        /**
         * @var \ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyDtoInputOutput
         */
        $resourceObject = $context[AbstractItemNormalizer::OBJECT_TO_POPULATE] ?? new $context['resource_class']();
        $resourceObject->str = $data->foo;
        $resourceObject->num = $data->bar;

        return $resourceObject;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($object, string $to, array $context = []): bool
    {
        if ($object instanceof DummyDtoInputOutput || $object instanceof DummyDtoInputOutputDocument) {
            return false;
        }

        return (DummyDtoInputOutput::class === $to || DummyDtoInputOutputDocument::class === $to) && (InputDto::class === $context['input']['class']);
    }
}
