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

namespace ApiPlatform\Metadata\Tests\Property\Factory;

use ApiPlatform\Metadata\Extractor\PropertyExtractorInterface;
use ApiPlatform\Metadata\Property\Factory\ExtractorPropertyMetadataFactory;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\Comment;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

final class ExtractorPropertyMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateVirtualPropertyFromExtractor(): void
    {
        $extractorProphecy = $this->prophesize(PropertyExtractorInterface::class);
        $extractorProphecy->getProperties()->willReturn([
            Comment::class => [
                'admin' => [
                    'security' => 'user.isAdmin() === true',
                ],
            ],
        ]);

        $factory = new ExtractorPropertyMetadataFactory($extractorProphecy->reveal());
        $metadata = $factory->create(Comment::class, 'admin');

        self::assertSame('user.isAdmin() === true', $metadata->getSecurity());
    }
}
