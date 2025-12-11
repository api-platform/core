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
use ApiPlatform\State\Pagination\MappedObjectPaginator;
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
        $class = $operation->getOutput()['class'] ?? $operation->getClass();

        if (!$this->objectMapper || !$operation->canMap()) {
            return $data;
        }

        if (!\is_object($data) && !\is_array($data)) {
            return $data;
        }

        $request = $context['request'] ?? null;
        $request?->attributes->set('mapped_data', $data);

        if ($data instanceof PaginatorInterface) {
            $data = new MappedObjectPaginator(
                iterator_to_array($data),
                $this->objectMapper,
                $class,
                $data->getTotalItems(),
                $data->getCurrentPage(),
                $data->getLastPage(),
                $data->getItemsPerPage(),
            );
        } elseif (\is_array($data)) {
            foreach ($data as &$v) {
                if (\is_object($v)) {
                    $v = $this->objectMapper->map($v, $class);
                }
            }
        } else {
            $data = $this->objectMapper->map($data, $class);
        }

        $request?->attributes->set('data', $data);
        $request?->attributes->set('previous_data', $this->clone($data));

        return $data;
    }
}
