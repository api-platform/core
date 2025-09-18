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

namespace ApiPlatform\Serializer\State;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Symfony\Component\JsonStreamer\StreamReaderInterface;
use Symfony\Component\TypeInfo\Type;

final class JsonStreamerProvider implements ProviderInterface
{
    public function __construct(
        private readonly ?ProviderInterface $decorated,
        private readonly StreamReaderInterface $jsonStreamReader,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (!$operation instanceof HttpOperation || !$operation->getJsonStream() || !($request = $context['request'] ?? null)) {
            return $this->decorated?->provide($operation, $uriVariables, $context);
        }

        $data = $this->decorated ? $this->decorated->provide($operation, $uriVariables, $context) : $request->attributes->get('data');

        if (!$operation->canDeserialize() || 'json' !== $request->attributes->get('input_format')) {
            return $data;
        }

        $data = $this->jsonStreamReader->read($request->getContent(true), Type::object($operation->getClass()));
        $context['request']->attributes->set('deserialized', true);

        return $data;
    }
}
