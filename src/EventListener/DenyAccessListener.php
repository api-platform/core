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

namespace ApiPlatform\Core\EventListener;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Denies access to the current resource if the logged user doesn't have sufficient permissions.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class DenyAccessListener
{
    private $resourceMetadataFactory;
    private $authorizationChecker;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, AuthorizationCheckerInterface $authorizationChecker = null)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
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
            $isGranted = $resourceMetadata->getCollectionOperationAttribute($attributes['collection_operation_name'], 'is_granted', null, true);
        } else {
            $isGranted = $resourceMetadata->getItemOperationAttribute($attributes['item_operation_name'], 'is_granted', null, true);
        }

        if (null === $isGranted) {
            return;
        }

        if (null === $this->authorizationChecker) {
            throw new \LogicException(sprintf('The "symfony/security" library must be installed to use the "is_granted" attribute on class "%s".', $attributes['resource_class']));
        }

        if (!class_exists(Expression::class)) {
            throw new \LogicException(sprintf('The "symfony/expression-language" library must be installed to use the "is_granted" attribute on class "%s".', $attributes['resource_class']));
        }

        if (!$this->authorizationChecker->isGranted(new Expression($isGranted), $request->attributes->get('data'))) {
            throw new AccessDeniedException();
        }
    }
}
