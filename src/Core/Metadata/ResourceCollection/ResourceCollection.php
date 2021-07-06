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

namespace ApiPlatform\Core\Metadata\ResourceCollection;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\ApiResource;

/**
 * @experimental
 * @extends \ArrayObject<int, ApiResource>
 */
final class ResourceCollection extends \ArrayObject
{
    private $operationCache = [];

    public function getOperation(?string $operationName): ?Operation
    {
        if (!$operationName) {
            return null;
        }

        if (isset($this->operationCache[$operationName])) {
            return $this->operationCache[$operationName];
        }

        $it = $this->getIterator();

        while ($it->valid()) {
            /** @var resource */
            $metadata = $it->current();

            foreach ($metadata->getOperations() as $name => $operation) {
                if ($name === $operationName) {
                    return $operation;
                }
            }

            $it->next();
        }

        return null;
    }

    public function getFirstOperation(): ?array
    {
        $it = $this->getIterator();

        while ($it->valid()) {
            /** @var resource */
            $metadata = $it->current();

            foreach ($metadata->getOperations() as $name => $operation) {
                if ($operation->getMethod() === Operation::METHOD_GET && !$operation->isCollection()) {
                    return [$name, $operation];
                }
            }

            $it->next();
        }

        return [null, null];
    }
}
