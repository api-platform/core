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

#[Get(uriTemplate: 'resource_a',
    formats: ['jsonhal'],
    outputFormats: ['jsonhal'],
    normalizationContext: ['groups' => ['ResourceA:read'], 'enable_max_depth' => true],
    provider: [self::class, 'provide'])]
final class ResourceA
{
    private static ?ResourceA $resourceA = null;

    #[ApiProperty(readableLink: true)]
    #[Groups(['ResourceA:read', 'ResourceB:read'])]
    #[MaxDepth(6)]
    public ResourceB $b;

    public function __construct(?ResourceB $b = null)
    {
        if (null !== $b) {
            $this->b = $b;
        }
    }

    public static function provide(): self
    {
        return self::provideWithResource();
    }

    public static function provideWithResource(?ResourceB $b = null): self
    {
        if (!isset(self::$resourceA)) {
            self::$resourceA = new self($b);

            if (null === ResourceB::getInstance()) {
                self::$resourceA->b = ResourceB::provideWithResource(self::$resourceA);
            }
        }

        return self::$resourceA;
    }

    public static function getInstance(): ?self
    {
        return self::$resourceA;
    }
}
