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

namespace ApiPlatform\Metadata;

use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;

/**
 * @internal
 *
 * @phpstan-require-extends ApiResource
 */
trait CascadeToOperationsTrait
{
    public function cascadeToOperations(): static
    {
        if (!$this instanceof ApiResource) {
            throw new RuntimeException('Not an API resource');
        }

        if (!($operations = $this->getOperations() ?? [])) {
            return $this;
        }

        return (clone $this)->withOperations(
            new Operations($this->getMutatedOperations($operations, $this)),
        );
    }

    public function cascadeToGraphQlOperations(): static
    {
        if (!$this instanceof ApiResource) {
            throw new RuntimeException('Not an API resource');
        }

        if (!($operations = $this->getGraphQlOperations() ?? [])) {
            return $this;
        }

        return (clone $this)->withGraphQlOperations(
            $this->getMutatedOperations($operations, $this),
        );
    }

    /**
     * @param Operations<HttpOperation>|list<GraphQlOperation> $operations
     *
     * @return array<string, HttpOperation>|list<GraphQlOperation>
     */
    private function getMutatedOperations(iterable $operations, ApiResource $apiResource): iterable
    {
        $modifiedOperations = [];
        foreach ($operations as $key => $operation) {
            $modifiedOperations[$key] = $operation->cascadeFromResource($apiResource);
        }

        return $modifiedOperations;
    }
}
