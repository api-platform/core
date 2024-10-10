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

namespace ApiPlatform\Serializer;

use ApiPlatform\State\SerializerContextBuilderInterface as StateSerializerContextBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * {@inheritdoc}
 *
 * @author Maxime Hélias <maximehelias16@gmail.com>
 */
final class RequestCacheSerializerContextBuilder implements SerializerContextBuilderInterface
{
    public function __construct(private readonly SerializerContextBuilderInterface|StateSerializerContextBuilderInterface $decorated)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function createFromRequest(Request $request, bool $normalization, ?array $extractedAttributes = null): array
    {
        $attributeKey = $normalization ? '_api_normalization_context' : '_api_denormalization_context';

        if ($request->attributes->has($attributeKey)) {
            return $request->attributes->get($attributeKey);
        }

        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);

        $request->attributes->set($attributeKey, $context);

        return $context;
    }
}
