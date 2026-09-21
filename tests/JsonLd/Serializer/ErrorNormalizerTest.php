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

namespace ApiPlatform\Tests\JsonLd\Serializer;

use ApiPlatform\JsonLd\ContextBuilder;
use ApiPlatform\JsonLd\Serializer\ErrorNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ErrorNormalizerTest extends TestCase
{
    public function testAddHydraPrefix(): void
    {
        $provider = $this->createMock(NormalizerInterface::class);
        $provider->method('normalize')->willReturn(['@type' => 'Error', 'title' => 'foo', 'description' => 'bar']);
        $errorNormalizer = new ErrorNormalizer($provider, ['hydra_prefix' => ContextBuilder::HYDRA_CONTEXT_HAS_PREFIX]);
        $res = $errorNormalizer->normalize(new \stdClass());
        $this->assertSame('hydra:Error', $res['@type']);
        $this->assertArrayHasKey('hydra:description', $res);
        $this->assertSame($res['hydra:description'], 'bar');
        $this->assertArrayHasKey('hydra:title', $res);
        $this->assertSame($res['hydra:title'], 'foo');
        $this->assertArrayNotHasKey('title', $res);
        $this->assertArrayNotHasKey('description', $res);
    }
}
