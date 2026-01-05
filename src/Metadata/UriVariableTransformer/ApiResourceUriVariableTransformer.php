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

namespace ApiPlatform\Metadata\UriVariableTransformer;

use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UriVariableTransformerInterface;

final class ApiResourceUriVariableTransformer implements UriVariableTransformerInterface
{
    public function __construct(private readonly IdentifiersExtractorInterface $identifiersExtractor, private readonly ResourceClassResolverInterface $resourceClassResolver)
    {
    }

    public function transform(mixed $value, array $types, array $context = []): mixed
    {
        return current($this->identifiersExtractor->getIdentifiersFromItem($value));
    }

    public function supportsTransformation(mixed $value, array $types, array $context = []): bool
    {
        return \is_object($value) && $this->resourceClassResolver->isResourceClass($value::class);
    }
}
