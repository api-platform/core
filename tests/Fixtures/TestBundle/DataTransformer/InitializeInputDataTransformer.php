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

use ApiPlatform\Core\DataTransformer\DataTransformerInitializerInterface;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\InitializeInput as InitializeInputDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\InitializeInputDto;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\InitializeInput;

final class InitializeInputDataTransformer implements DataTransformerInitializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($object, string $to, array $context = [])
    {
        /** @var InitializeInputDto */
        $data = $object;

        /** @var InitializeInput|InitializeInputDocument */
        $resourceObject = $context[AbstractItemNormalizer::OBJECT_TO_POPULATE] ?? new $context['resource_class']();
        $resourceObject->name = $data->name;

        return $resourceObject;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(string $inputClass, array $context = [])
    {
        $currentResource = $context[AbstractItemNormalizer::OBJECT_TO_POPULATE] ?? null;
        if (!$currentResource) {
            return new InitializeInputDto();
        }

        $dto = new InitializeInputDto();
        $dto->manager = $currentResource->manager;

        return $dto;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return (InitializeInput::class === $to || InitializeInputDocument::class === $to) && InitializeInputDto::class === ($context['input']['class'] ?? null);
    }
}
