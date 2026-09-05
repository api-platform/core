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

namespace ApiPlatform\Metadata\Util;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as SymfonyContainerInterface;

/**
 * Replaces Symfony container parameters (%param%) found in resource configuration values.
 *
 * The substitution logic mirrors Symfony's router (and the YAML/XML resource extractors):
 * %% escapes a literal %, env() parameters are forbidden, and a parameter must resolve to a
 * scalar. It is intentionally kept free of any symfony/dependency-injection requirement so the
 * standalone metadata component can rely on it through a PSR ContainerInterface.
 *
 * @see https://github.com/symfony/symfony/blob/6fec32c/src/Symfony/Bundle/FrameworkBundle/Routing/Router.php
 *
 * @copyright (c) Fabien Potencier <fabien@symfony.com>
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class ContainerParameterResolver
{
    private array $collectedParameters = [];

    public function __construct(private readonly ?ContainerInterface $container = null)
    {
    }

    /**
     * Resolves every %param% reference found anywhere in $value (router-style substitution).
     *
     * @throws \RuntimeException When a container value is not a string or a numeric value
     */
    public function resolve(mixed $value): mixed
    {
        if (null === $this->container) {
            return $value;
        }

        if (\is_array($value)) {
            foreach ($value as $key => $val) {
                $value[$key] = $this->resolve($val);
            }

            return $value;
        }

        if (!\is_string($value)) {
            return $value;
        }

        $escapedValue = preg_replace_callback('/%%|%([^%\s]++)%/', function ($match) use ($value) {
            $parameter = $match[1] ?? null;

            // skip %%
            if (!isset($parameter)) {
                return '%%';
            }

            if (preg_match('/^env\(\w+\)$/', $parameter)) {
                throw new \RuntimeException(\sprintf('Using "%%%s%%" is not allowed in resource configuration.', $parameter));
            }

            if (\array_key_exists($parameter, $this->collectedParameters)) {
                return $this->collectedParameters[$parameter];
            }

            if ($this->container instanceof SymfonyContainerInterface) {
                $resolved = $this->container->getParameter($parameter);
            } else {
                $resolved = $this->container->get($parameter);
            }

            if (\is_string($resolved) || is_numeric($resolved)) {
                return $this->collectedParameters[$parameter] = (string) $resolved;
            }

            throw new \RuntimeException(\sprintf('The container parameter "%s", used in the resource configuration value "%s", must be a string or numeric, but it is of type %s.', $parameter, $value, get_debug_type($resolved)));
        }, $value);

        return str_replace('%%', '%', $escapedValue);
    }

    /**
     * Resolves a container parameter in an ExpressionLanguage field (security, condition, …) only
     * when the whole trimmed value is a single %param% reference. Such a value is invalid
     * ExpressionLanguage on its own, so resolving it cannot break a working expression. Any other
     * use of "%" — partial, or a real modulo like "object.value % 2" — is left untouched so it
     * reaches the expression engine verbatim.
     */
    public function resolveExpressionPlaceholder(mixed $value): mixed
    {
        if (!\is_string($value) || !preg_match('/^%[^%\s]+%$/', trim($value))) {
            return $value;
        }

        return $this->resolve(trim($value));
    }
}
