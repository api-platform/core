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

namespace ApiPlatform\Symfony\EventListener;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Symfony\Security\ExpressionLanguage;
use ApiPlatform\Symfony\Security\ResourceAccessChecker;
use ApiPlatform\Symfony\Security\ResourceAccessCheckerInterface;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * Denies access to the current resource if the logged user doesn't have sufficient permissions.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class DenyAccessListener
{
    use OperationRequestInitiatorTrait;

    /**
     * @var ResourceMetadataFactoryInterface|ResourceMetadataCollectionFactoryInterface
     */
    private $resourceMetadataFactory;
    /**
     * @var ResourceAccessCheckerInterface
     */
    private $resourceAccessChecker;

    public function __construct($resourceMetadataFactory, /* ResourceAccessCheckerInterface */ $resourceAccessCheckerOrExpressionLanguage = null, AuthenticationTrustResolverInterface $authenticationTrustResolver = null, RoleHierarchyInterface $roleHierarchy = null, TokenStorageInterface $tokenStorage = null, AuthorizationCheckerInterface $authorizationChecker = null)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;

        if ($resourceAccessCheckerOrExpressionLanguage instanceof ResourceAccessCheckerInterface) {
            $this->resourceAccessChecker = $resourceAccessCheckerOrExpressionLanguage;

            return;
        }

        $this->resourceAccessChecker = new ResourceAccessChecker($resourceAccessCheckerOrExpressionLanguage, $authenticationTrustResolver, $roleHierarchy, $tokenStorage, $authorizationChecker);
        @trigger_error(sprintf('Passing an instance of "%s" or null as second argument of "%s" is deprecated since API Platform 2.2 and will not be possible anymore in API Platform 3. Pass an instance of "%s" and no extra argument instead.', ExpressionLanguage::class, self::class, ResourceAccessCheckerInterface::class), \E_USER_DEPRECATED);

        if (!$resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use "%s" instead of "%s".', ResourceMetadataCollectionFactoryInterface::class, ResourceMetadataFactoryInterface::class));
        } else {
            $this->resourceMetadataCollectionFactory = $resourceMetadataFactory;
        }
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        @trigger_error(sprintf('Method "%1$s::onKernelRequest" is deprecated since API Platform 2.4 and will not be available anymore in API Platform 3. Prefer calling "%1$s::onSecurity" instead.', self::class), \E_USER_DEPRECATED);
        $this->onSecurityPostDenormalize($event);
    }

    public function onSecurity(RequestEvent $event): void
    {
        $this->checkSecurity($event->getRequest(), 'security', false);
    }

    public function onSecurityPostDenormalize(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $this->checkSecurity($request, 'security_post_denormalize', true, [
            'previous_object' => $request->attributes->get('previous_data'),
        ]);
    }

    public function onSecurityPostValidation(ViewEvent $event): void
    {
        $request = $event->getRequest();
        $this->checkSecurity($request, 'security_post_validation', false, [
            'previous_object' => $request->attributes->get('previous_data'),
        ]);
    }

    /**
     * @throws AccessDeniedException
     */
    private function checkSecurity(Request $request, string $attribute, bool $backwardCompatibility, array $extraVariables = []): void
    {
        if (!$attributes = RequestAttributesExtractor::extractAttributes($request)) {
            return;
        }

        $resourceMetadata = null;
        $isGranted = null;
        $message = $attributes[$attribute.'_message'] ?? 'Access Denied.';

        if ($this->resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
            $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);

            $isGranted = $resourceMetadata->getOperationAttribute($attributes, $attribute, null, true);
            if ($backwardCompatibility && null === $isGranted) {
                // Backward compatibility
                $isGranted = $resourceMetadata->getOperationAttribute($attributes, 'access_control', null, true);
                if (null !== $isGranted) {
                    @trigger_error('Using "access_control" attribute is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Use "security" attribute instead.', \E_USER_DEPRECATED);
                }
            }

            $message = $resourceMetadata->getOperationAttribute($attributes, $attribute.'_message', 'Access Denied.', true);
        } elseif ($this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            $operation = $this->initializeOperation($request);
            if (!$operation) {
                return;
            }

            switch ($attribute) {
                case 'security_post_denormalize':
                    $isGranted = $operation->getSecurityPostDenormalize();
                    $message = $operation->getSecurityPostDenormalizeMessage();
                    break;
                case 'security_post_validation':
                    $isGranted = $operation->getSecurityPostValidation();
                    $message = $operation->getSecurityPostValidationMessage();
                    break;
                default:
                    $isGranted = $operation->getSecurity();
                    $message = $operation->getSecurityMessage();
            }
        }

        if (null === $isGranted) {
            return;
        }

        $extraVariables += $request->attributes->all();
        $extraVariables['object'] = $request->attributes->get('data');
        $extraVariables['previous_object'] = $request->attributes->get('previous_data');
        $extraVariables['request'] = $request;

        if (!$this->resourceAccessChecker->isGranted($attributes['resource_class'], $isGranted, $extraVariables)) {
            throw new AccessDeniedException($message ?? 'Access Denied.');
        }
    }
}

class_alias(DenyAccessListener::class, \ApiPlatform\Core\Security\EventListener\DenyAccessListener::class);
