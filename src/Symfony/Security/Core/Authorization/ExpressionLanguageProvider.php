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

namespace ApiPlatform\Symfony\Security\Core\Authorization;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

/**
 * Registers API Platform's Expression Language functions.
 *
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 */
final class ExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions(): array
    {
        return [
            new ExpressionFunction('is_granted', static fn ($attributes, $object = 'null'): string => \sprintf('$auth_checker->isGranted(%s, %s, $access_decision ?? null)', $attributes, $object), static fn (array $variables, $attributes, $object = null) => $variables['auth_checker']->isGranted($attributes, $object, $variables['access_decision'] ?? null)),
        ];
    }
}
