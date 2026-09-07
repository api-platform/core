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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\McpTool;
use ApiPlatform\Metadata\Operation;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[ApiResource(
    shortName: 'McpExceptionTools',
    operations: [],
    mcp: [
        'symfony_not_found_provider_tool' => new McpTool(
            provider: [self::class, 'provideNotFound'],
        ),
        'symfony_not_found_processor_tool' => new McpTool(
            processor: [self::class, 'processNotFound'],
        ),
    ]
)]
class McpExceptionTools
{
    public function __construct(private ?string $text = null)
    {
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): void
    {
        $this->text = $text;
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public static function provideNotFound(Operation $operation, array $uriVariables = [], array $context = []): never
    {
        throw new NotFoundHttpException('Provider says this resource does not exist.');
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public static function processNotFound(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): never
    {
        throw new NotFoundHttpException('Processor says this resource does not exist.');
    }
}
