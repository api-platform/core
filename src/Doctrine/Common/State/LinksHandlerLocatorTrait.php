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

namespace ApiPlatform\Doctrine\Common\State;

use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Operation;
use Psr\Container\ContainerInterface;

/**
 * @internal
 */
trait LinksHandlerLocatorTrait
{
    private ?ContainerInterface $handleLinksLocator;

    private function getLinksHandler(Operation $operation): ?callable
    {
        if (!($options = $operation->getStateOptions()) || !$options instanceof Options) {
            return null;
        }

        $handleLinks = $options->getHandleLinks();
        if (\is_callable($handleLinks)) {
            return $handleLinks;
        }

        if (\is_array($handleLinks) && 2 === \count($handleLinks) && class_exists($handleLinks[0])) {
            [$className, $methodName] = $handleLinks;

            if (method_exists($className, $methodName)) {
                return $handleLinks;
            }

            $suggestedMethod = $this->findSimilarMethod($className, $methodName);

            throw new RuntimeException(\sprintf('Method "%s" does not exist in class "%s".%s', $methodName, $className, $suggestedMethod ? \sprintf(' Did you mean "%s"?', $suggestedMethod) : ''));
        }

        if ($this->handleLinksLocator && \is_string($handleLinks) && $this->handleLinksLocator->has($handleLinks)) {
            return [$this->handleLinksLocator->get($handleLinks), 'handleLinks'];
        }

        throw new RuntimeException(\sprintf('Could not find handleLinks service "%s"', $handleLinks));
    }

    private function findSimilarMethod(string $className, string $methodName): ?string
    {
        $methods = get_class_methods($className);

        $similarMethods = array_filter($methods, function ($method) use ($methodName) {
            return levenshtein($methodName, $method) <= 3;
        });

        return $similarMethods ? reset($similarMethods) : null;
    }
}
