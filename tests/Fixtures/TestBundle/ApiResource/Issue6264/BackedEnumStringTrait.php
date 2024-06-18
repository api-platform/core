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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6264;

use ApiPlatform\Metadata\Operation;
use Symfony\Component\Serializer\Attribute\Groups;

trait BackedEnumStringTrait
{
    public static function values(): array
    {
        return array_map(static fn (\BackedEnum $feature) => $feature->value, self::cases());
    }

    public function getId(): string
    {
        return $this->value;
    }

    #[Groups(['get'])]
    public function getValue(): string
    {
        return $this->value;
    }

    public static function getCases(): array
    {
        return self::cases();
    }

    /**
     * @param array<string, string> $uriVariables
     */
    public static function getCase(Operation $operation, array $uriVariables): ?self
    {
        return array_reduce(self::cases(), static fn ($c, \BackedEnum $case) => $case->value == $uriVariables['id'] ? $case : $c, null);
    }
}
