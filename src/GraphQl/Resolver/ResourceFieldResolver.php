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

namespace ApiPlatform\GraphQl\Resolver;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\Util\ClassInfoTrait;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * A field resolver that resolves IDs to IRIs and allow to access to the raw ID using the "#id" field.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ResourceFieldResolver
{
    use ClassInfoTrait;

    private $iriConverter;

    public function __construct(IriConverterInterface $iriConverter)
    {
        $this->iriConverter = $iriConverter;
    }

    public function __invoke(?array $source, array $args, $context, ResolveInfo $info)
    {
        $property = null;
        if ('id' === $info->fieldName && !isset($source['_id']) && isset($source[ItemNormalizer::ITEM_RESOURCE_CLASS_KEY], $source[ItemNormalizer::ITEM_IDENTIFIERS_KEY])) {
            return $this->iriConverter->getIriFromResource($source[ItemNormalizer::ITEM_RESOURCE_CLASS_KEY], UrlGeneratorInterface::ABS_PATH, null, ['uri_variables' => $source[ItemNormalizer::ITEM_IDENTIFIERS_KEY]]);
        }

        if ('_id' === $info->fieldName && !isset($source['_id']) && isset($source['id'])) {
            $property = $source['id'];
        } elseif (\is_array($source) && isset($source[$info->fieldName])) {
            $property = $source[$info->fieldName];
        } elseif (isset($source->{$info->fieldName})) {
            $property = $source->{$info->fieldName};
        }

        return $property instanceof \Closure ? $property($source, $args, $context) : $property;
    }
}
