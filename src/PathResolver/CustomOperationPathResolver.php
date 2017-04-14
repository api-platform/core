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

namespace ApiPlatform\Core\PathResolver;

/**
 * Resolves the custom operations path.
 *
 * @author Guilhem N. <egetick@gmail.com>
 */
final class CustomOperationPathResolver implements OperationPathResolverInterface
{
    private $deferred;

    public function __construct(OperationPathResolverInterface $deferred)
    {
        $this->deferred = $deferred;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveOperationPath(string $resourceShortName, array $operation, bool $collection): string
    {
        if (isset($operation['path'])) {
            return $operation['path'];
        }

        return $this->deferred->resolveOperationPath($resourceShortName, $operation, $collection);
    }
}
