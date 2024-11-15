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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue4358;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

#[Get(uriTemplate: 'resource_b',
    formats: ['jsonhal'],
    outputFormats: ['jsonhal'],
    normalizationContext: ['groups' => ['ResourceB:read'], 'enable_max_depth' => true],
    provider: [self::class, 'provide'])]
final class ResourceB
{
    private static ?ResourceB $resourceB = null;

    #[ApiProperty(readableLink: true)]
    #[Groups(['ResourceA:read', 'ResourceB:read'])]
    #[MaxDepth(6)]
    public ResourceA $a;

    public function __construct(?ResourceA $a = null)
    {
        if (null !== $a) {
            $this->a = $a;
        }
    }

    public static function provide(): self
    {
        return self::provideWithResource();
    }

    public static function provideWithResource(?ResourceA $a = null): self
    {
        if (!isset(self::$resourceB)) {
            self::$resourceB = new self($a);

            if (null === ResourceA::getInstance()) {
                self::$resourceB->a = ResourceA::provideWithResource(self::$resourceB);
            }
        }

        return self::$resourceB;
    }

    public static function getInstance(): ?self
    {
        return self::$resourceB;
    }
}
