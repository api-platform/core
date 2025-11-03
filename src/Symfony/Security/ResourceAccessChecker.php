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

use ApiPlatform\Metadata\ResourceAccessCheckerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\Node\NameNode;
use Symfony\Component\ExpressionLanguage\Node\Node;
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
final class ResourceAccessChecker implements ResourceAccessCheckerInterface, ObjectVariableCheckerInterface
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

        return (bool) $this->expressionLanguage->evaluate($expression, $this->getVariables($extraVariables));
    }

    public function usesObjectVariable(string $expression, array $variables = []): bool
    {
        return $this->hasObjectVariable($this->expressionLanguage->parse($expression, array_keys($this->getVariables($variables)))->getNodes()->toArray());
    }

    /**
     * @copyright Fabien Potencier <fabien@symfony.com>
     *
     * @see https://github.com/symfony/symfony/blob/master/src/Symfony/Component/Security/Core/Authorization/Voter/ExpressionVoter.php
     */
    private function getVariables(array $variables): array
    {
        if (null === $token = $this->tokenStorage->getToken()) {
            $token = new NullToken();
        }

        return array_merge($variables, [
            'token' => $token,
            'user' => $token->getUser(),
            'roles' => $this->getEffectiveRoles($token),
            'trust_resolver' => $this->authenticationTrustResolver,
            'auth_checker' => $this->authorizationChecker, // needed for the is_granted expression function
        ]);
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

    /**
     * Recursively checks if a variable named 'object' is present in the expression AST.
     *
     * @param Node|array<mixed>|null $nodeOrNodes the ExpressionLanguage Node instance or an array of nodes/values
     */
    private function hasObjectVariable(Node|array|null $nodeOrNodes): bool
    {
        if ($nodeOrNodes instanceof NameNode) {
            if ('object' === $nodeOrNodes->attributes['name'] || 'previous_object' === $nodeOrNodes->attributes['name']) {
                return true;
            }

            return false;
        }

        if ($nodeOrNodes instanceof Node) {
            foreach ($nodeOrNodes->nodes as $childNode) {
                if ($this->hasObjectVariable($childNode)) {
                    return true;
                }
            }

            return false;
        }

        if (\is_array($nodeOrNodes)) {
            foreach ($nodeOrNodes as $element) {
                if (\is_string($element)) {
                    continue;
                }

                if ($this->hasObjectVariable($element)) {
                    return true;
                }
            }
        }

        return false;
    }
}
