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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Enum;

final class ContentStatus implements \JsonSerializable
{
    public const DRAFT = 'draft';
    public const PUBLISHED = 'published';

    private $value;

    public function __construct(string $value)
    {
        if (!self::isValid($value)) {
            throw new \UnexpectedValueException("Value '$value' is not part of the enum ".__CLASS__);
        }

        $this->value = $value;
    }

    /**
     * @return string|bool
     */
    public function getKey()
    {
        return static::search($this->value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return array<string, string>
     */
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

    /**
     * @return string|bool
     */
    public static function search(string $value)
    {
        return array_search($value, self::toArray(), true);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'key' => $this->getKey(),
            'value' => $this->getValue(),
        ];
    }
}
