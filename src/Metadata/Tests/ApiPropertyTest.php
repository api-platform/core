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

namespace ApiPlatform\Metadata\Tests;

use ApiPlatform\Metadata\ApiProperty;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\MaxDepth;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Attribute\SerializedPath;

final class ApiPropertyTest extends TestCase
{
    public function testWithSerializeWrapsASingleAttribute(): void
    {
        $groups = new Groups(['read']);

        $this->assertSame([$groups], (new ApiProperty())->withSerialize($groups)->getSerialize());
        $this->assertSame((new ApiProperty(serialize: $groups))->getSerialize(), (new ApiProperty())->withSerialize($groups)->getSerialize());
    }

    #[DataProvider('provideSerializeAttributes')]
    public function testWithSerializeWrapsEachAttributeType(object $attribute): void
    {
        $this->assertSame([$attribute], (new ApiProperty())->withSerialize($attribute)->getSerialize());
    }

    /**
     * @return iterable<string, array{object}>
     */
    public static function provideSerializeAttributes(): iterable
    {
        yield 'Context' => [new Context(['enable_max_depth' => true])];
        yield 'Groups' => [new Groups(['read'])];
        yield 'Ignore' => [new Ignore()];
        yield 'MaxDepth' => [new MaxDepth(2)];
        yield 'SerializedName' => [new SerializedName('full_name')];
        yield 'SerializedPath' => [new SerializedPath('[profile][name]')];
    }

    public function testWithSerializeKeepsAList(): void
    {
        $serialize = [new Groups(['read']), new SerializedName('full_name')];

        $this->assertSame($serialize, (new ApiProperty())->withSerialize($serialize)->getSerialize());
    }
}
