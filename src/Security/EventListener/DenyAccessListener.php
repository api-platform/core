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

namespace ApiPlatform\Core\Security\EventListener;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Security\ExpressionLanguage;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * Denies access to the current resource if the logged user doesn't have sufficient permissions.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class DenyAccessListener
{
    private $resourceMetadataFactory;
    private $expressionLanguage;
    private $authenticationTrustResolver;
    private $roleHierarchy;
    private $tokenStorage;
    private $authorizationChecker;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, ExpressionLanguage $expressionLanguage = null, AuthenticationTrustResolverInterface $authenticationTrustResolver = null, RoleHierarchyInterface $roleHierarchy = null, TokenStorageInterface $tokenStorage = null, AuthorizationCheckerInterface $authorizationChecker = null)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->expressionLanguage = $expressionLanguage;
        $this->authenticationTrustResolver = $authenticationTrustResolver;
        $this->roleHierarchy = $roleHierarchy;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * Sets the applicable format to the HttpFoundation Request.
     *
     * @param GetResponseEvent $event
     *
     * @throws AccessDeniedException
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!$attributes = RequestAttributesExtractor::extractAttributes($request)) {
            return;
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);
        if (isset($attributes['collection_operation_name'])) {
            $isGranted = $resourceMetadata->getCollectionOperationAttribute($attributes['collection_operation_name'], 'access_control', null, true);
        } elseif (isset($attributes['item_operation_name'])) {
            $isGranted = $resourceMetadata->getItemOperationAttribute($attributes['item_operation_name'], 'access_control', null, true);
        } else {
            $isGranted = $resourceMetadata->getCollectionOperationAttribute($attributes['subresource_operation_name'], 'access_control', null, true);
        }

        if (null === $isGranted) {
            return;
        }

        if (null === $this->tokenStorage || null === $this->authenticationTrustResolver) {
            throw new \LogicException(sprintf('The "symfony/security" library must be installed to use the "access_control" attribute on class "%s".', $attributes['resource_class']));
        }
        if (null === $this->tokenStorage->getToken()) {
            throw new \LogicException(sprintf('The resource must be behind a firewall to use the "access_control" attribute on class "%s".', $attributes['resource_class']));
        }
        if (null === $this->expressionLanguage) {
            throw new \LogicException(sprintf('The "symfony/expression-language" library must be installed to use the "access_control" attribute on class "%s".', $attributes['resource_class']));
        }

        if (!$this->expressionLanguage->evaluate($isGranted, $this->getVariables($request))) {
            throw new AccessDeniedException();
        }
    }

    /**
     * @copyright Fabien Potencier <fabien@symfony.com>
     *
     * @see https://github.com/symfony/symfony/blob/master/src/Symfony/Component/Security/Core/Authorization/Voter/ExpressionVoter.php
     */
    private function getVariables(Request $request): array
    {
        $token = $this->tokenStorage->getToken();
        $roles = $this->roleHierarchy ? $this->roleHierarchy->getReachableRoles($token->getRoles()) : $token->getRoles();

        $variables = [
            'token' => $token,
            'user' => $token->getUser(),
            'object' => $request->attributes->get('data'),
            'request' => $request,
            'roles' => array_map(function (Role $role) {
                return $role->getRole();
            }, $roles),
            'trust_resolver' => $this->authenticationTrustResolver,
            // needed for the is_granted expression function
            'auth_checker' => $this->authorizationChecker,
        ];

        // controller variables should also be accessible
        return array_merge($request->attributes->all(), $variables);
    }
}
