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

namespace ApiPlatform\Symfony\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Component\Mercure\Discovery;

/**
 * @template T1
 * @template T2
 *
 * @implements ProcessorInterface<T1, T2>
 */
final class MercureLinkProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<T1, T2> $decorated
     */
    public function __construct(private readonly ProcessorInterface $decorated, private readonly Discovery $discovery)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!($request = $context['request'] ?? null) || !$mercure = $operation->getMercure()) {
            return $this->decorated->process($data, $operation, $uriVariables, $context);
        }

        $hub = \is_array($mercure) ? ($mercure['hub'] ?? null) : null;
        $this->discovery->addLink($request, $hub);

        return $this->decorated->process($data, $operation, $uriVariables, $context);
    }
}
