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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Enum;

final class ContentStatus implements \JsonSerializable
{
    public const DRAFT = 'draft';
    public const PUBLISHED = 'published';

    private readonly string $value;

    public function __construct(string $value)
    {
        if (!self::isValid($value)) {
            throw new \UnexpectedValueException("Value '$value' is not part of the enum ".self::class);
        }

        $this->value = $value;
    }

    public function getKey(): string|bool
    {
        return static::search($this->value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public static function toArray(): array
    {
        return [
            'DRAFT' => self::DRAFT,
            'PUBLISHED' => self::PUBLISHED,
        ];
    }

    public static function isValid(string $value): bool
    {
        return \in_array($value, self::toArray(), true);
    }

    public static function search(string $value): string|bool
    {
        return array_search($value, self::toArray(), true);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return [
            'key' => $this->getKey(),
            'value' => $this->getValue(),
        ];
    }
}
