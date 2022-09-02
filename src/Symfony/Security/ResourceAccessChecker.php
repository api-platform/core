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

namespace ApiPlatform\Symfony\Security;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * Checks if the logged user has sufficient permissions to access the given resource.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ResourceAccessChecker implements ResourceAccessCheckerInterface
{
    public function __construct(private readonly ?ExpressionLanguage $expressionLanguage = null, private readonly ?AuthenticationTrustResolverInterface $authenticationTrustResolver = null, private readonly ?RoleHierarchyInterface $roleHierarchy = null, private readonly ?TokenStorageInterface $tokenStorage = null, private readonly ?AuthorizationCheckerInterface $authorizationChecker = null)
    {
    }

    public function isGranted(string $resourceClass, string $expression, array $extraVariables = []): bool
    {
        if (null === $this->tokenStorage || null === $this->authenticationTrustResolver) {
            throw new \LogicException('The "symfony/security" library must be installed to use the "security" attribute.');
        }

        if (null === $this->expressionLanguage) {
            throw new \LogicException('The "symfony/expression-language" library must be installed to use the "security" attribute.');
        }

        $variables = array_merge($extraVariables, [
            'trust_resolver' => $this->authenticationTrustResolver,
            'auth_checker' => $this->authorizationChecker, // needed for the is_granted expression function
        ]);

        if (null === $token = $this->tokenStorage->getToken()) {
            $token = new NullToken();
        }

        $variables = array_merge($variables, $this->getVariables($token));

        return (bool) $this->expressionLanguage->evaluate($expression, $variables);
    }

    /**
     * @copyright Fabien Potencier <fabien@symfony.com>
     *
     * @see https://github.com/symfony/symfony/blob/master/src/Symfony/Component/Security/Core/Authorization/Voter/ExpressionVoter.php
     */
    private function getVariables(TokenInterface $token): array
    {
        return [
            'token' => $token,
            'user' => $token->getUser(),
            'roles' => $this->getEffectiveRoles($token),
        ];
    }

    /**
     * @return string[]
     */
    private function getEffectiveRoles(TokenInterface $token): array
    {
        if (null === $this->roleHierarchy) {
            return $token->getRoleNames();
        }

        return $this->roleHierarchy->getReachableRoleNames($token->getRoleNames());
    }
}
