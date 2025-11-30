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

namespace ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Util\CloneTrait;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\ProviderInterface;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

/**
 * @implements ProviderInterface<object>
 */
final class ObjectMapperProvider implements ProviderInterface
{
    use CloneTrait;

    /**
     * @param ProviderInterface<object> $decorated
     */
    public function __construct(
        private readonly ?ObjectMapperInterface $objectMapper,
        private readonly ProviderInterface $decorated,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $data = $this->decorated->provide($operation, $uriVariables, $context);

        if (!$this->objectMapper || !$operation->canMap()) {
            return $data;
        }

        if (!\is_object($data) && !\is_array($data)) {
            return $data;
        }

        $request = $context['request'] ?? null;
        $request?->attributes->set('mapped_data', $data);

        if ($data instanceof PaginatorInterface) {
            $data = new ArrayPaginator(array_map(fn ($v) => $this->objectMapper->map($v, $operation->getClass()), iterator_to_array($data)), 0, \count($data));
        } elseif (\is_array($data)) {
            foreach ($data as &$v) {
                if (\is_object($v)) {
                    $v = $this->objectMapper->map($v, $operation->getClass());
                }
            }
        } else {
            $data = $this->objectMapper->map($data, $operation->getClass());
        }

        $request?->attributes->set('data', $data);
        $request?->attributes->set('previous_data', $this->clone($data));

        return $data;
    }
}
