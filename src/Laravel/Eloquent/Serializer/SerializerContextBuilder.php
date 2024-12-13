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

namespace ApiPlatform\Laravel\Eloquent\Serializer;

use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\State\SerializerContextBuilderInterface;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

final class SerializerContextBuilder implements SerializerContextBuilderInterface
{
    public function __construct(
        private readonly SerializerContextBuilderInterface $decorated,
        private readonly PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory,
    ) {
    }

    /**
     * @param array<string, mixed> $extractedAttributes
     */
    public function createFromRequest(Request $request, bool $normalization, ?array $extractedAttributes = null): array
    {
        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);
        if (!isset($context['resource_class']) || !is_a($context['resource_class'], Model::class, true)) {
            return $context;
        }

        if (!isset($context[AbstractNormalizer::ATTRIBUTES])) {
            // isWritable/isReadable is checked later on
            $context[AbstractNormalizer::ATTRIBUTES] = iterator_to_array($this->propertyNameCollectionFactory->create($context['resource_class'], ['serializer_groups' => $context['groups'] ?? null]));
        }

        return $context;
    }
}
