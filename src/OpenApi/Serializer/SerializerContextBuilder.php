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

namespace ApiPlatform\OpenApi\Serializer;

use ApiPlatform\State\SerializerContextBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
final class SerializerContextBuilder implements SerializerContextBuilderInterface
{
    public function __construct(private readonly SerializerContextBuilderInterface $decorated)
    {
    }

    public function createFromRequest(Request $request, bool $normalization, ?array $extractedAttributes = null): array
    {
        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);

        return $context + [
            'api_gateway' => $request->query->getBoolean(ApiGatewayNormalizer::API_GATEWAY),
            'base_url' => $request->getBaseUrl(),
            'spec_version' => (string) $request->query->get(LegacyOpenApiNormalizer::SPEC_VERSION),
        ];
    }
}
