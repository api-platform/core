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

namespace ApiPlatform\Metadata\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Operation;

/**
 * @experimental
 * @extends \ArrayObject<int, ApiResource>
 */
final class ResourceMetadataCollection extends \ArrayObject
{
    private $operationCache = [];

    public function getOperation(?string $operationName): Operation
    {
        if (!$operationName) {
            return $this->getFirstOperation();
        }

        if (isset($this->operationCache[$operationName])) {
            return $this->operationCache[$operationName];
        }

        $it = $this->getIterator();

        while ($it->valid()) {
            /** @var resource */
            $metadata = $it->current();

            foreach ($metadata->getOperations() as $name => $operation) {
                if (null === $operationName && \in_array([Operation::METHOD_GET, Operation::METHOD_OPTIONS, Operation::METHOD_HEAD], $operation->getMethod(), true) && !$operation->isCollection()) {
                    return $operation;
                }

                if ($name === $operationName) {
                    return $operation;
                }
            }

            $it->next();
        }

        // throw new OperationNotFoundException();
    }
}
