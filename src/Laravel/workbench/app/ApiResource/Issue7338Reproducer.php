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

namespace Workbench\App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;

#[ApiResource(
    uriTemplate: '/issue7338_reproducers/{id}',
    operations: [
        new Get(
            output: Issue7338Output::class,
            uriTemplate: '/issue7338_reproducers/{id}/output',
            provider: [self::class, 'provide'],
            normalizationContext: ['groups' => ['issue7338:output:read']]
        ),
        new Post(
            input: Issue7338Input::class,
            uriTemplate: '/issue7338_reproducers/input',
            processor: [self::class, 'process'],
            denormalizationContext: ['groups' => ['issue7338:input:write']],
        ),
    ]
)]
class Issue7338Reproducer
{
    public function __construct(public ?int $id = null, public ?string $title = null)
    {
        $this->id = $id;
    }

    public static function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        return new Issue7338Output((int) $uriVariables['id'], 'Test Name', new \DateTimeImmutable());
    }

    public static function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        \assert(null === $data->description);

        return new self(1, $data->title);
    }
}
