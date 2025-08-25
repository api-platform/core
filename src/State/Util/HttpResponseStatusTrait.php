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

namespace ApiPlatform\State\Util;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use ApiPlatform\Metadata\Util\CloneTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait HttpResponseStatusTrait
{
    use ClassInfoTrait;
    use CloneTrait;
    private ?ResourceClassResolverInterface $resourceClassResolver;

    public const METHOD_TO_CODE = [
        'POST' => Response::HTTP_CREATED,
        'DELETE' => Response::HTTP_NO_CONTENT,
    ];

    /**
     * @param array<string, mixed> $context
     */
    private function getStatus(Request $request, HttpOperation $operation, array $context): int
    {
        $status = $operation->getStatus();
        $method = $request->getMethod();

        $outputMetadata = $operation->getOutput() ?? ['class' => $operation->getClass()];
        $hasOutput = \is_array($outputMetadata) && \array_key_exists('class', $outputMetadata) && null !== $outputMetadata['class'];
        $originalData = $context['original_data'] ?? null;
        $hasData = !$hasOutput ? false : ($this->resourceClassResolver && $originalData && \is_object($originalData) && $this->resourceClassResolver->isResourceClass($this->getObjectClass($originalData)));

        if ($hasData) {
            if ('PUT' === $method && !$request->attributes->get('previous_data') && null === $status && ($operation instanceof Put && ($operation->getAllowCreate() ?? false))) {
                $status = Response::HTTP_CREATED;
            }
        }

        return $status ?? self::METHOD_TO_CODE[$method] ?? Response::HTTP_OK;
    }
}
