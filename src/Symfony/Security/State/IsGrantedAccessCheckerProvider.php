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

namespace ApiPlatform\Symfony\Security\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Symfony\Security\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Denies access based on the ApiPlatform\Metadata\ResourceAccessCheckerInterface.
 * This implementation covers HTTP.
 *
 * @see ResourceAccessCheckerInterface
 */
final class IsGrantedAccessCheckerProvider implements ProviderInterface
{
    public function __construct(private readonly ProviderInterface $decorated, private readonly ?AuthorizationCheckerInterface $authChecker = null)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (!$this->authChecker) {
            return $this->decorated->provide($operation, $uriVariables, $context);
        }

        $s = $operation->getSecurity();
        if (null === $s) {
            return $this->decorated->provide($operation, $uriVariables, $context);
        }

        if (!\is_array($s)) {
            $s = [$s];
        }

        foreach ($s as $isGranted) {
            if (!$isGranted instanceof IsGranted) {
                continue;
            }

            if (!$this->authChecker->isGranted($isGranted->attribute, $isGranted->subject)) {
                $message = $isGranted->message ?: 'Access denied';
                throw new AccessDeniedException($message);
            }
        }

        return $this->decorated->provide($operation, $uriVariables, $context);
    }
}
