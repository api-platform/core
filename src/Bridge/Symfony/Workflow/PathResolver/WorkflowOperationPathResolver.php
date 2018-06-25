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

namespace ApiPlatform\Core\Bridge\Symfony\Workflow\PathResolver;

use ApiPlatform\Core\PathResolver\OperationPathResolverInterface;

/**
 * If any corresponding metadata, append the `_path_suffix` to the operation's path.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class WorkflowOperationPathResolver implements OperationPathResolverInterface
{
    const PATH_FORMAT_SUFFIX = '.{_format}';
    private $decorated;

    public function __construct(OperationPathResolverInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveOperationPath(string $resourceShortName, array $operation, $operationType/*, string $operationName = null*/): string
    {
        $path = $this->decorated->resolveOperationPath($resourceShortName, $operation, $operationType, null);

        if (!isset($operation['_path_suffix'])) {
            return $path;
        }

        if (self::PATH_FORMAT_SUFFIX === substr($path, -10)) {
            return str_replace(self::PATH_FORMAT_SUFFIX, $operation['_path_suffix'].self::PATH_FORMAT_SUFFIX, $path);
        }

        return "$path{$operation['_path_suffix']}";
    }
}
