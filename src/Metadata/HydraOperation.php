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

namespace ApiPlatform\Metadata;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class HydraOperation
{
    /**
     * @param string                  $method     HTTP method (GET, POST, PUT, PATCH, DELETE)
     * @param array<string>|null      $types      Hydra/schema.org types (e.g. ['Operation', 'schema:DeleteAction']). When null, a sensible default is derived from $method.
     * @param string|\Stringable|null $security   ExpressionLanguage expression evaluated at serialization time. The expression has access to `object`, `user`, `request`, `auth_checker`. When the expression evaluates to false, the operation is omitted from the response.
     * @param bool                    $collection Whether the operation applies to the collection (true) or to an item (false)
     */
    public function __construct(
        private readonly string $method,
        private readonly bool $collection = false,
        private readonly string|\Stringable|null $security = null,
        private readonly ?string $title = null,
        private readonly ?string $description = null,
        private readonly ?array $types = null,
        private readonly ?string $expects = null,
        private readonly ?string $returns = null,
        private readonly array $extraProperties = [],
    ) {
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getCollection(): bool
    {
        return $this->collection;
    }

    public function getSecurity(): ?string
    {
        return $this->security instanceof \Stringable ? (string) $this->security : $this->security;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return array<string>|null
     */
    public function getTypes(): ?array
    {
        return $this->types;
    }

    public function getExpects(): ?string
    {
        return $this->expects;
    }

    public function getReturns(): ?string
    {
        return $this->returns;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraProperties(): array
    {
        return $this->extraProperties;
    }
}
